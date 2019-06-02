<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommand extends AppBaseCommand
{
    const FINDDUPLICATES_OUTPUT_FILENAME = 'photosort_duplicates.json';

    protected static $defaultName = 'app:find-duplicates';

    private $hasher;

    private $sourceFile;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->hasher = $hashService;

        parent::__construct($filesystem);
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
        $this->persistInput($input, $output);
        $this->persistArgs($input);

        $data = $this->readJsonFile($this->sourceFile);

        $result = $this->findDuplicates($data);

        $this->writeJsonFile(dirname($this->sourceFile) . DIRECTORY_SEPARATOR . self::FINDDUPLICATES_OUTPUT_FILENAME, $result);
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

    private function persistArgs(InputInterface $input)
    {
        $this->sourceFile = $input->getArgument('source-file');

        $this->ensureSourceExists($this->sourceFile);
    }
}