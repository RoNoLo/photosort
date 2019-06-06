<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class HashMergeCommand extends AppBaseCommand
{
    const HASHMERGE_OUTPUT_MERGE_FILENAME = 'photosort_hashs_merged.json';
    const HASHMERGE_OUTPUT_DUPLICATES_HELPER_FILENAME = 'photosort_hashs_duplicates_helper.json';

    protected static $defaultName = 'app:merge';

    // Args
    private $sources;

    private $outputPath;

    protected function configure()
    {
        $this->setDescription('Merges hash files.');
        $this->setHelp('Merges and convertes multiple hash files into bigger files.');

        $this->addArgument('sources', InputArgument::IS_ARRAY, 'Source hash files (separated by space).');
        $this->addOption('output-path', 'o', InputOption::VALUE_OPTIONAL, 'Path to output JSON files (default: will write in first source-path)', null);
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

        $data = $this->readJsonFilesAndMerge($this->sources);

        $outputPath = $this->ensureOutputFilePath();

        $mergedFilePath = $outputPath . DIRECTORY_SEPARATOR . self::HASHMERGE_OUTPUT_MERGE_FILENAME;
        $duplicatesHelperFilePath = $outputPath . DIRECTORY_SEPARATOR . self::HASHMERGE_OUTPUT_DUPLICATES_HELPER_FILENAME;

        $this->writeJsonFile($mergedFilePath, $data);

        $hash = [];
        foreach ($data as $filepath => $items) {
            foreach ($items as $item) {
                $hash[$item] = $filepath;
            }
        }

        $this->writeJsonFile($duplicatesHelperFilePath, $hash);
    }

    private function ensureOutputFilePath()
    {
        if (is_null($this->outputPath)) {
            return realpath(dirname($this->sources[0]));
        }

        if ($this->filesystem->exists($this->outputPath)) {
            $realpath = realpath($this->outputPath);

            return $realpath;
        }
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sources = $input->getArgument('sources');
        $this->outputPath = $input->getOption('output-path');

        $this->ensureSources();
        $this->ensureOutputPath();
    }

    private function ensureSources()
    {
        if (!count($this->sources)) {
            throw new InvalidArgumentException("No source hash files were given.");
        }

        foreach ($this->sources as $source) {
            if (!$this->filesystem->exists($source)) {
                throw new InvalidArgumentException("Source hash file `{$source}` does not exists.");
            }
        }
    }

    private function ensureOutputPath()
    {
        if (is_null($this->outputPath)) {
            return;
        }

        if ($this->filesystem->exists($this->outputPath) && is_dir($this->outputPath)) {
            return;
        }

        if ($this->filesystem->exists($this->outputPath) && is_file($this->outputPath)) {
            throw new InvalidArgumentException("The output should be an directory not a file.");
        }

        throw new InvalidArgumentException("The output directory does not exist or is not accessible.");
    }
}