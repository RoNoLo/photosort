<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class HashCommand extends AppBaseCommand
{
    const HASHMAP_IMAGES = ['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'];
    const HASHMAP_OUTPUT_FILENAME = 'photosort_hashmap.json';

    protected static $defaultName = 'app:hash';

    /** @var HashService */
    private $hasher;

    /** @var string */
    private $sourcePath;

    /** @var string */
    private $outputFile;

    /** @var null|int */
    private $chunk;

    public function __construct(HashService $hashService)
    {
        $this->hasher = $hashService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Creates a message digest JSON for every file in a given path recursively.');
        $this->setHelp("Creates a message digest JSON file, which may help to find duplicate files quicker.\nWhen the PHP extension imagick is found image signatures are added to find duplicated images based on the pixel-stream.");

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source root path');
        $this->addOption('output-file', 'o', InputOption::VALUE_REQUIRED, 'Path to output JSON file (default: will write in source-path)', null);
        $this->addOption('chunk', 'c', InputOption::VALUE_OPTIONAL, 'Amount of files to process. Will continue an existing output-file', null);
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

        $finder = Finder::create()
            ->files()
            ->name(self::HASHMAP_IMAGES)
            ->in($this->sourcePath)
        ;

        $fileCount = $finder->count();
        if ($output->isVeryVerbose()) {
            $output->writeln($fileCount . " files found.");
        }

        if (!is_null($this->chunk)) {
            if ($fileCount > $this->chunk) {
                $fileCount = $this->chunk;
            }
        }

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar = new ProgressBar($output, $fileCount);
            $progressBar->start();
        }

        $outputFile = $this->ensureOutputFile();

        $hashs = [];
        if (!is_null($this->chunk) && $this->filesystem->exists($outputFile)) {
            $hashs = $this->readJsonFile($outputFile);
        }

        $i = 1;
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();

            if (!is_null($this->chunk) && isset($hashs[$filePath])) {
                continue;
            }

            $result = $this->hasher->hashFile($filePath, true);
            $hashs[$filePath] = $result;

            $countStatistic = "";
            if ($output->isVeryVerbose()) {
                $countStatistic = " (" . $i . "/" . $fileCount . ")";
            }

            if ($output->isVerbose()) {
                $output->writeln($result['sha1'] . " - " . $filePath . $countStatistic);
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $progressBar->advance();
            }

            if (!is_null($this->chunk)) {
                if ($i >= $fileCount) {
                    break;
                }
            }

            $i++;
        }

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar->finish();
        }

        $this->writeJsonFile($outputFile, $hashs);

        if ($output->isVerbose()) {
            $output->writeln('Result: ' . $outputFile);
        }
    }

    private function ensureOutputFile()
    {
        if (is_null($this->outputFile)) {
            return $this->sourcePath . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
        }

        $pathInfo = pathinfo($this->outputFile);

        // Is it just a filename?
        if ($pathInfo['basename'] == basename($this->outputFile) && $pathInfo['dirname'] == "." && $pathInfo['extension'] == 'json') {
            return '.' . DIRECTORY_SEPARATOR . $this->outputFile;
        }

        // Is it just a directory?
        if (!empty($pathInfo['basename']) && !empty($pathInfo['dirname']) && $pathInfo['extension'] == "json") {
            return $this->outputFile;
        }

        // Fallback
        return '.' . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourcePath = $input->getArgument('source-path');
        $this->outputFile = $input->getOption('output-file');
        $this->chunk = $input->getOption('chunk');

        $this->ensureSource();
        $this->ensureOutput();
        $this->ensureChunk();
    }

    private function ensureSource()
    {
        if (!$this->filesystem->exists($this->sourcePath)) {
            throw new InvalidArgumentException("The source-path directory does not exist or is not accessible.");
        }
    }

    private function ensureOutput()
    {
        if (is_null($this->outputFile)) {
            return;
        }

        if ($this->filesystem->exists($this->outputFile) && is_dir($this->outputFile)) {
            throw new InvalidOptionException("The --output-file option should be an file-path.");
        }

        if (!$this->filesystem->exists($this->outputFile) && $this->filesystem->exists(dirname($this->outputFile)) && is_dir(dirname($this->outputFile))) {
            return;
        }

        if ($this->filesystem->exists($this->outputFile) && is_file($this->outputFile)) {
            return;
        }

        throw new InvalidOptionException("The --output-file option file path does not exist or is not accessible.");
    }

    private function ensureChunk()
    {
        if (is_null($this->chunk)) {
            return;
        }

        if (!is_numeric($this->chunk)) {
            throw new InvalidOptionException("The --chunk option needs to be an integer.");
        }

        $this->chunk = intval($this->chunk);

        if ($this->chunk <= 0) {
            throw new InvalidOptionException("The --chunk option needs to be a positive integer.");
        }
    }
}