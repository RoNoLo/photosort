<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicatesFindCommand extends AppBaseCommand
{
    const FINDDUPLICATES_OUTPUT_FILENAME = 'photosort_duplicates.json';

    protected static $defaultName = 'app:dups';

    /** @var HashService */
    private $hasher;

    /** @var string[] */
    private $sources;

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

        return $duplicates;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sources = $input->getArgument('sources');

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

        foreach ($data as $file => $hashs) {
            if (isset($hashs['sha1']) && !empty($hashs['sha1'])) {
                $sha1[$hashs['sha1']][] = $file;
            }
            if (isset($hashs['signature']) && !empty($hashs['signature'])) {
                $signatures[$hashs['signature']][] = $file;
            }
        }

        return [$sha1, $signatures];
    }

    private function removeUniqueFilePaths(&$list)
    {
        foreach ($list as $hash => $files) {
            if (count($files) <= 1) {
                unset($list[$hash]);
            }
        }
    }

    private function mergeDigestLists($sha1, $signatures)
    {
        // Time to merge the results
        $duplicates = array_values($sha1);

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
            }

            $duplicates = array_merge($duplicates, $duplicatesSignature);
        }

        foreach ($duplicates as $i => $files) {
            $duplicates[$i] = array_unique($files);
        }

        return $duplicates;
    }
}