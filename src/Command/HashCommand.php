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

/**
 * Command to Hash files and create image digests.
 *
 * Todo: add .hashignore files support
 *
 * @package App\Command
 */
class HashCommand extends AppBaseCommand
{
    const HASH_OUTPUT_FILENAME = 'photosort_hashmap.json';
    const HASH_CHUNK_SAVE = 100;

    protected static $defaultName = 'app:hash';

    /** @var HashService */
    private $hasher;

    /** @var string */
    private $sourcePath;

    /** @var string */
    private $outputFile;

    /** @var string[] */
    private $fileMask;

    /** @var bool */
    private $update = false;

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
        $this->addOption('file-mask', 'm', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'List of Finder Component compatible name filter (separated by space). Default is an common images only definition. Set to "*.*" to hash everything.', []);
        $this->addOption('update', null, InputOption::VALUE_NONE, 'This will update the hash-file. It removes not existing file hashes and adds newly found files to the hash-file.');
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

        $files = $this->fetchFiles();

        if (!$files->hasResults()) {
            $this->output->writeln("Nothing to do. No files found.");
            return;
        }

        $fileCount = $files->count();
        if ($output->isVeryVerbose()) {
            $output->writeln($fileCount . " files found.");
        }

        // On normal verbosity, a progressbar is shown
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar = new ProgressBar($output, $fileCount);
            $progressBar->start();
        }

        $outputFile = $this->ensureOutputFile();

        $hashs = [];

        // If a hash JSON file exists it will continue
        if ($this->filesystem->exists($outputFile)) {
            $hashs = $this->readJsonFile($outputFile);

            if ($output->isVerbose()) {
                $output->writeln("Existing Hash file found at: " . $outputFile);
            }
        }

        $i = 0;
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $i++;
            $filePath = $file->getRealPath();

            if (isset($hashs[$filePath])) {
                continue;
            }

            try {
                $result = $this->hasher->hashFile($filePath, true);
            } catch (\Exception $e) {
                // We just ignore the - may be wrong - extensioned file
                continue;
            }

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

            // This will auto save the file every 100 hashs.
            if ($i % self::HASH_CHUNK_SAVE == 0) {
                $this->writeJsonFile($outputFile, $hashs);

                if ($this->output->isVerbose()) {
                    $this->output->writeln('Saving: ' . $outputFile);
                }
            }
        }

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
            $progressBar->finish();
        }

        // If we are in update mode, we will also check if every file from the hash-file still exists.
        if ($this->update && count($hashs)) {
            if ($this->output->isVerbose()) {
                $this->output->writeln('Checking hashfile for removed files');
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $progressBar = new ProgressBar($output, count($hashs));
                $progressBar->start();
            }

            foreach ($hashs as $filePath => $ignore) {
                if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                    $progressBar->advance();
                }

                if (!$this->filesystem->exists($filePath)) {
                    if ($this->output->isVerbose()) {
                        $this->output->writeln('Removing: ' . $filePath);
                    }

                    unset($hashs[$filePath]);
                }
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $progressBar->finish();
            }
        }

        $this->writeJsonFile($outputFile, $hashs);

        if ($output->isVerbose()) {
            $output->writeln('Result: ' . $outputFile);
        }

        return 0;
    }

    private function ensureOutputFile()
    {
        if (is_null($this->outputFile)) {
            return $this->sourcePath . DIRECTORY_SEPARATOR . self::HASH_OUTPUT_FILENAME;
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
        return '.' . DIRECTORY_SEPARATOR . self::HASH_OUTPUT_FILENAME;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourcePath = $input->getArgument('source-path');
        $this->outputFile = $input->getOption('output-file');
        $this->fileMask = $input->getOption('file-mask');
        $this->update = $input->getOption('update');

        $this->ensureSource();
        $this->ensureOutput();
        $this->ensureFileMask();
        $this->ensureUpdate();
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

    private function fetchFiles()
    {
        $finder = Finder::create()
            ->files()
            ->name($this->fileMask)
            ->in($this->sourcePath)
        ;

        return $finder;
    }

    private function ensureFileMask()
    {
        if (!is_array($this->fileMask)) {
            $this->fileMask = [];
        }

        if (!count($this->fileMask)) {
            $this->fileMask = ["*.jpg", "*.jpeg", "*.JPG", "*.tif", "*.tiff", "*.png", "*.gif", "*.raw", "*.bmp"];
        }
    }

    private function ensureUpdate()
    {
        if (!$this->update) {
            return;
        }

        $outputFile = $this->ensureOutputFile();

        if ($this->filesystem->exists($outputFile) && is_file($outputFile)) {
            return;
        }

        throw new InvalidOptionException("The --update option expects an already existing hash-file. Nothing found.");
    }
}