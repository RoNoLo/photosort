<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DupsCommand extends AppBaseCommand
{
    const FINDDUPLICATES_OUTPUT_FILENAME = 'photosort_duplicates.json';

    protected static $defaultName = 'app:dups';

    /** @var HashService */
    private $hasher;

    /** @var string[] */
    private $sources;

    /** @var bool */
    private $info;

    public function __construct(HashService $hashService)
    {
        $this->hasher = $hashService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Finds duplicates in the hash map.');
        $this->setHelp('Analyses and filters the hash map JSON file for duplicate files. Every available message digest will be used and compared.');

        $this->addArgument('sources', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Path to the hash files created with the app:hash command (separated by space).');
        $this->addOption('info', 'i', InputOption::VALUE_NONE, 'Will gather and print out statistics about the duplicates.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->persistInput($input, $output);
        $this->persistArgs($input);

        $data = $this->readJsonFilesAndMerge($this->sources);

        $result = $this->findDuplicates($data);

        $this->writeJsonFile(dirname($this->sources[0]) . DIRECTORY_SEPARATOR . self::FINDDUPLICATES_OUTPUT_FILENAME, $result);
    }

    private function findDuplicates(&$data)
    {
        // First collecting all files per hash
        [$sha1, $signatures] = $this->transformDataByDigests($data);
        $this->removeUniqueFilePaths($sha1);
        $this->removeUniqueFilePaths($signatures);
        $duplicates = $this->mergeDigestLists($sha1, $signatures);

        if ($this->info) {
            $this->gatherInfoDuplicatedFileSizes($duplicates);
        }

        return $duplicates;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sources = $input->getArgument('sources');
        $this->info = !!$input->getOption('info');

        $this->ensureSourcesExists($this->sources);
    }

    private function ensureSourcesExists(array $sources)
    {
        if (!count($this->sources)) {
            throw new InvalidArgumentException("No source hash files were given.");
        }

        foreach ($sources as $source) {
            if (!$this->filesystem->exists($source)) {
                throw new InvalidArgumentException("Source hash file `{$source}` does not exists.");
            }
        }
    }

    private function transformDataByDigests(&$data)
    {
        $sha1 = [];
        $signatures = [];

        $progressBar = new ProgressBar($this->output, count($data));
        $this->output->writeln("Sort data by message digest / signature");

        foreach ($data as $file => $hashs) {
            if (isset($hashs['sha1']) && !empty($hashs['sha1'])) {
                $sha1[$hashs['sha1']][] = $file;
            }
            if (isset($hashs['signature']) && !empty($hashs['signature'])) {
                $signatures[$hashs['signature']][] = $file;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->write("\n");

        return [$sha1, $signatures];
    }

    private function removeUniqueFilePaths(&$list)
    {
        $progressBar = new ProgressBar($this->output, count($list));
        $this->output->writeln("Removing unique file entries");

        foreach ($list as $hash => $files) {
            if (count($files) <= 1) {
                unset($list[$hash]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->write("\n");
    }

    private function mergeDigestLists($sha1, $signatures)
    {
        // Time to merge the results
        $duplicates = array_values($sha1);

        $progressBar = new ProgressBar($this->output, count($duplicates));
        $this->output->writeln("Merging data");

        if (count($signatures)) {
            $duplicatesSignature = array_values($signatures);

            foreach ($duplicates as $i => $files) {
                foreach ($duplicatesSignature as $j => $items) {
                    $diff = array_diff($files, $items);

                    if (count($diff) != count($files)) {
                        $duplicates[$i] = array_merge($files, $items);
                        unset($duplicatesSignature[$j]);
                    }
                }

                $progressBar->advance();
            }

            $duplicates = array_merge($duplicates, $duplicatesSignature);
        }

        $progressBar->finish();
        $this->output->write("\n");

        foreach ($duplicates as $i => $files) {
            $duplicates[$i] = array_unique($files);
        }

        return $duplicates;
    }

    private function gatherInfoDuplicatedFileSizes(array $duplicates)
    {
        $log = [
            'unique' => 0, // Bytes of the list[0] files
            'duplicates' => 0, // Bytes of all duplicated files
            'not_found' => [], // Files that be be already removed
        ];

        $progressBar = new ProgressBar($this->output, count($duplicates));
        $this->output->writeln("Gathering data");

        foreach ($duplicates as $files) {
            foreach ($files as $i => $file) {
                if ($this->filesystem->exists($file)) {
                    $fileSize = filesize($file);

                    if ($fileSize === false) {
                        continue;
                    }

                    if ($i == 0) {
                        $log['unique'] += $fileSize;
                    } else {
                        $log['duplicates'] += $fileSize;
                    }
                } else {
                    $log['not_found'][] = $file;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->write("\n");

        $this->output->writeln("Uniques: " . $log['unique'] . " Bytes");
        $this->output->writeln("Duplicates: " . $log['duplicates'] . " Bytes");

        if (count($log['not_found'])) {
            $this->output->writeln("Not found: " . $log['not_found'] . " Bytes");
        }
    }
}