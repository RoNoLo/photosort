<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Stopwatch\Stopwatch;

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

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar = new ProgressBar($output, $fileCount);
            $progressBar->start();
        }

        $hashs = [];
        $i = 1;
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $result = $this->hasher->hashFile($filePath, true);
            $hashs[$filePath] = $result;

            $countStatistic = "";
            if ($output->isVeryVerbose()) {
                $countStatistic = " (" . $i++ . "/" . $fileCount . ")";
            }

            if ($output->isVerbose()) {
                $output->writeln($result['sha1'] . " - " . $filePath . $countStatistic);
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $progressBar->advance();
            }
        }

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar->finish();
        }

        $outputFile = $this->ensureOutputFile();

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

        if (empty($pathInfo['filename']) && !empty($pathInfo['dirname']) && $pathInfo['dirname'] !== ".") {
            return $pathInfo['dirname'] . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
        }

        return '.' . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourcePath = $input->getArgument('source-path');
        $this->outputFile = $input->getOption('output-file');

        $this->ensureSource();
        $this->ensureOutput();
    }

    private function ensureSource()
    {
        if (!$this->filesystem->exists($this->sourcePath)) {
            throw new IOException("The source-path directory does not exist or is not accessible.");
        }
    }

    private function ensureOutput()
    {
        if (is_null($this->outputFile)) {
            return;
        }

        if ($this->filesystem->exists($this->outputFile) && is_dir($this->outputFile)) {
            throw new IOException("The --output-file option should be an file-path.");
        }

        if (!$this->filesystem->exists($this->outputFile) && $this->filesystem->exists(dirname($this->outputFile)) && is_dir(dirname($this->outputFile))) {
            return;
        }

        throw new IOException("The --output-file option file path does not exist or is not accessible.");
    }
}