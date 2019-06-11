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

class MergeCommand extends AppBaseCommand
{
    const HASHMERGE_OUTPUT_MERGE_FILENAME = 'photosort_hashs_merged.json';

    protected static $defaultName = 'app:merge';

    /** @var string[] */
    private $sources;

    /** @var string */
    private $outputFile;

    protected function configure()
    {
        $this->setDescription('Merges hash files.');
        $this->setHelp('Merges and convertes multiple hash files into bigger files.');

        $this->addArgument('sources', InputArgument::IS_ARRAY, 'Source hash files (separated by space).');
        $this->addOption('output-file', 'o', InputOption::VALUE_OPTIONAL, 'Path to output JSON file (default: will write in first source-path)', null);
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

        $outputFile = $this->ensureOutputFilePath();

        $this->writeJsonFile($outputFile, $data);
    }

    private function ensureOutputFilePath()
    {
        if (is_null($this->outputFile)) {
            return realpath(dirname($this->sources[0]) . DIRECTORY_SEPARATOR . self::HASHMERGE_OUTPUT_MERGE_FILENAME);
        }

        if ($this->filesystem->exists($this->outputFile)) {
            $realpath = realpath($this->outputFile);

            return $realpath;
        }

        return $this->outputFile;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sources = $input->getArgument('sources');
        $this->outputFile = $input->getOption('output-file');

        $this->ensureSources();
        $this->ensureOutputFile();
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

    private function ensureOutputFile()
    {
        if (is_null($this->outputFile)) {
            return;
        }

        if ($this->filesystem->exists($this->outputFile) && is_file($this->outputFile)) {
            return;
        }

        if ($this->filesystem->exists($this->outputFile) && is_dir($this->outputFile)) {
            throw new InvalidArgumentException("The option --output-file should point to a file.");
        }

        $pathInfo = pathinfo($this->outputFile);

        if (!empty($pathInfo['dirname']) && !empty($pathInfo['basename'])) {
            if ($pathInfo['extension'] !== 'json') {
                throw new InvalidArgumentException("The option --output-file should point to a JSON file.");
            }
        }
    }
}