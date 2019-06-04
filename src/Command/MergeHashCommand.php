<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MergeHashCommand extends AppBaseCommand
{
    const MERGEHASH_OUTPUT_MERGE_FILENAME = 'photosort_hashs_merged.json';
    const MERGEHASH_OUTPUT_SHA1_FILENAME = 'photosort_hashs_sha1s.json';
    const MERGEHASH_OUTPUT_SIGNATURE_FILENAME = 'photosort_hashs_signatures.json';

    protected static $defaultName = 'app:hash-merge';

    // Args
    private $sources;

    private $output;

    protected function configure()
    {
        $this->setDescription('Merges hash files.');
        $this->setHelp('Merges and convertes multiple hash files into bigger files.');

        $this->addArgument('source', InputArgument::IS_ARRAY, 'Source hash files (separated by space).');
        $this->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Path to output JSON files (default: will write in first source-path)', null);
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

        $data = [];
        foreach ($this->sources as $source) {
            $tmp = $this->readJsonFile($source);
            $data = array_merge($data, $tmp);
        }

        $output = $this->ensureOutputFilePath();

        $this->writeJsonFile()

    }

    private function ensureOutputFilePath()
    {
        if (is_null($this->output)) {
            return $this->sources[0];
        }

        if ($this->filesystem->exists($this->output)) {
            $realpath = realpath($this->output);

            return $realpath;
        }
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sources = $input->getArgument('source');
        $this->output = $input->getOption('output');

        $this->ensureSources($this->sources);
        $this->ensureOutputPath();
    }

    private function ensureSources(array $sources)
    {
        foreach ($sources as $source) {
            if (!$this->filesystem->exists($source)) {
                throw new IOException("Source hash file `{$source}` does not exists.");
            }
        }
    }

    private function ensureOutputPath()
    {
        if (is_null($this->output)) {
            return;
        }

        if ($this->filesystem->exists($this->output) && is_dir($this->output)) {
            return;
        }

        if ($this->filesystem->exists($this->output) && is_file($this->output)) {
            throw new IOException("The output should be an directory not a file.");
        }

        throw new IOException("The output directory does not exist or is not accessible.");
    }
}