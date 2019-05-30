<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommand extends Command
{
    const FINDDUPLICATES_OUTPUT_FILENAME = 'photosort_duplicates.json';

    protected static $defaultName = 'app:find-duplicates';

    private $filesystem;

    private $hasher;

    /** @var OutputInterface */
    private $output;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->filesystem = $filesystem;
        $this->hasher = $hashService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Analyses the hashmap.');
        $this->setHelp('Analyses and filters the hashmap based on options');

        $this->addArgument('source-file', InputArgument::REQUIRED, 'Hashmap');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        try {
            $sourceFile = $input->getArgument('source-file');

            $this->ensureSourceExists($sourceFile);

            $data = json_decode(file_get_contents(realpath($sourceFile)), JSON_PRETTY_PRINT);

            $result = $this->findDuplicates($data);

            $this->filesystem->dumpFile(
                dirname($sourceFile) . DIRECTORY_SEPARATOR . self::FINDDUPLICATES_OUTPUT_FILENAME,
                json_encode($result, JSON_PRETTY_PRINT)
            );
        } catch (\Exception $e) {
            die ("Error: " . $e->getMessage());
        }
    }

    private function findDuplicates($data)
    {
        $sha1 = [];
        $signatures = [];

        // First collecting all files per hash
        foreach ($data as $file => $hashs) {
            if (isset($hashs['sha1']) && !empty($hashs['sha1'])) {
                $sha1[$hashs['sha1']][] = $file;
            }
            if (isset($hashs['signature']) && !empty($hashs['signature'])) {
                $signatures[$hashs['signature']][] = $file;
            }
        }

        // Now we remove all unique images
        foreach ($sha1 as $hash => $files) {
            if (count($files) <= 1) {
                unset($sha1[$hash]);
            }
        }

        foreach ($signatures as $hash => $files) {
            if (count($files) <= 1) {
                unset($signatures[$hash]);
            }
        }

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

    private function ensureSourceExists(?string $source)
    {
        if (!$this->filesystem->exists($source)) {
            throw new IOException("Source hashmap file does not exists.");
        }
    }
}