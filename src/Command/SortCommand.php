<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * Class SortCommand
 *
 * @package App\Command
 */
class SortCommand extends AppBaseCommand
{
    const IMAGES = ['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'];
    const PHOTOSORT_OUTPUT_FILENAME = 'photosort_log.json';

    protected static $defaultName = 'app:sort';

    /** @var string */
    private $sourcePath;

    /** @var string */
    private $destinationPath;

    /** @var bool */
    private $noRename;

    /** @var bool */
    private $monthly;

    /** @var string|null */
    private $hashFile;

    /** @var \SplFileInfo */
    private $currentFile;

    /** @var array This will keep the actions performed for a log */
    private $log = [];

    /** @var int Errors on file handling */
    private $errors = 0;

    /** @var int Identical files found */
    private $identical = 0;

    /** @var int Total of processed files */
    private $total = 0;

    /** @var int Copied files */
    private $copied = 0;

    /** @var int Skipped files */
    private $skipped = 0;

    /** @var array Original format of the hash-map file. */
    private $hashsOriginal = [];

    /** @var HashService */
    private $hasher;

    /** @var array */
    private $hashs = [];

    public function __construct(HashService $hashService)
    {
        $this->hasher = $hashService;

        parent::__construct();
    }

    public function __destruct()
    {
        $this->writeLogfile();
    }

    protected function configure()
    {
        $this->setDescription('Copies images into a folder structure YY/YYMM/YYMMDD/files');
        $this->setHelp("This command will copy images into a folder structure based on the file date of the image.\nA log file (photosort_log.json) will be created in the source directory to see if everything went well. No image will be deleted or moved, just copy.");

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination-path', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('no-rename', 'x', InputOption::VALUE_NONE, 'Rename images which have the same name, but are not identical');
        $this->addOption('monthly', 'm', InputOption::VALUE_NONE, 'Sort only YY/YYMM/images instead of YY/YYMM/YYMMDD/images');
        $this->addOption('hash-file', 'f', InputOption::VALUE_REQUIRED, 'To find duplicates quicker, a file with precalculated hashs can be used.', null);
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

        $files = $this->findFiles();

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            if ($file->getSize() === 0) {
                $this->log[$this->currentFile->getPathname()] = 'skipped because the filesize was 0 bytes';
                $this->skipped++;
                continue;
            }

            $this->currentFile = $file;

            if ($this->hashFile) {
                try {
                    $hashs = $this->hasher->hashFile($file->getPathname(), true);

                    foreach ($hashs as $type => $hash) {
                        if (isset($this->hashs[$hash])) {
                            $this->log[$file->getPathname()] = 'Identical to ' . $this->hashs[$hash] . ' (found via duplicates hash)';
                            $this->identical++;
                            continue 2;
                        }
                    }
                } catch (\Exception $e) {
                    ; // Do nothing
                }
            }

            if ($output->isVerbose()) {
                $output->writeln("Image: " . $file->getBasename());
            }

            $imageDestinationFilePath = $this->buildDestinationPath($file);

            // If we use an hash-file, we will add new files here.
            if ($this->hashFile) {
                $this->hashsOriginal[$imageDestinationFilePath] = $hashs;

                // Here we add it to the list we match against in the ongoing sort process
                foreach ($hashs as $type => $hash) {
                    $this->hashs[$hash] = $imageDestinationFilePath;
                }
            }

            if ($output->isVeryVerbose()) {
                $output->writeln("Destination Path: " . $imageDestinationFilePath);
            }

            $imageSourceFilePath = $file->getRealPath();

            $this->copyFile($imageSourceFilePath, $imageDestinationFilePath);

            $this->total++;
        }

        $this->writeLogfile();

        if ($this->hashFile) {
            // Backup first
            $this->filesystem->copy($this->hashFile, $this->hashFile . '.' . date('YmdHis') . '.bak');
            $this->writeJsonFile($this->hashFile, $this->hashsOriginal);

            if ($output->isVerbose()) {
                $output->writeln('Updated: ' . $this->hashFile);
            }
        }

        return 0;
    }

    private function buildDestinationPath(\SplFileInfo $file)
    {
        $photoDate = $file->getMTime();

        $year = date("Y", $photoDate);
        $yearMonth = date("ym", $photoDate);
        $yearMonthDay = date("ymd", $photoDate);

        if ($this->monthly) {
            $destinationPath = $this->destinationPath . DIRECTORY_SEPARATOR .
              $year . DIRECTORY_SEPARATOR .
              $yearMonth . DIRECTORY_SEPARATOR .
              $file->getBasename()
            ;
        } else {
            $destinationPath = $this->destinationPath . DIRECTORY_SEPARATOR .
                $year . DIRECTORY_SEPARATOR .
                $yearMonth . DIRECTORY_SEPARATOR .
                $yearMonthDay . DIRECTORY_SEPARATOR .
                $file->getBasename()
            ;
        }

        return $destinationPath;
    }

    private function copyFile(string $imageSourceFilePath, string $imageDestinationFilePath)
    {
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("Try to copy from: " . $imageSourceFilePath . " to: ". $imageDestinationFilePath);
        }

        // Check if a file with the same name already exists at destination
        if ($this->filesystem->exists($imageDestinationFilePath)) {
            // When identical we abort the copy.
            if ($this->checkIdentical($imageSourceFilePath, $imageDestinationFilePath)) {
                $this->identical++;
                return;
            }

            // When different file with same name, we check if other files in the same path may be identical by hash
            if ($this->checkIdenticalInPath($imageSourceFilePath, dirname($imageDestinationFilePath))) {
                $this->identical++;
                return;
            }

            // So there is a identical named file and no other file in the destination path is identical
            if ($this->noRename) {
                $this->log[$this->currentFile->getPathname()] = 'Skipped because a file with identical name, but different content, was already at destination ' . $imageDestinationFilePath;
                $this->skipped++;

                if ($this->output->isDebug()) {
                    $this->output->writeln($this->currentFile->getPathname() . " has the same name as " . $imageDestinationFilePath . " but is not identical");
                    $this->output->writeln($this->currentFile->getPathname() . " was not copied");
                }

                return;
            }

            $imageDestinationFilePath = $this->renameDestinationFile($imageDestinationFilePath);

            if ($this->output->isDebug()) {
                $this->output->writeln($this->currentFile->getPathname() . " renamed to " . $imageDestinationFilePath);
            }
        }

        // Copy the file
        try {
            $this->filesystem->copy($imageSourceFilePath, $imageDestinationFilePath);
            $fileMTime = filemtime($imageSourceFilePath);
            if ($fileMTime) {
                touch($imageDestinationFilePath, $fileMTime);
            }

            $this->log[$this->currentFile->getPathname()] = 'Copy to ' . $imageDestinationFilePath;
            $this->copied++;

            if ($this->output->isVerbose()) {
                $this->output->writeln("Copy from: " . $this->currentFile->getPathname() . " to: ". $imageDestinationFilePath);
            }
        } catch (IOException $e) {
            $this->errors++;
            $this->log[$this->currentFile->getPathname()] = 'Error on copy ' . $e->getMessage();
        }
    }

    private function checkIdentical($sourceFile, $destinationFile)
    {
        $result = $this->hasher->compareFile($sourceFile, $destinationFile, true);

        if ($result) {
            $this->log[$this->currentFile->getPathname()] = 'Identical to ' . $destinationFile;

            if ($this->output->isDebug()) {
                $this->output->writeln($this->currentFile->getPathname() . " is identical to " . $destinationFile);
            }

            return true;
        }

        return false;
    }

    private function checkIdenticalInPath(string $sourceFile, string $destinationPath)
    {
        $finder = new Finder();

        $finder->files()->name(self::IMAGES)->in($destinationPath);

        $sourceFileSize = filesize($sourceFile);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if ($file->isDir()) {
                continue;
            }

            if ($file->getSize() === 0) {
                continue;
            }

            // as a small speedhack, only files which size will diff by max 10% will be checked
            if (!$this->checkFileSize($sourceFileSize, $file->getSize())) {
                continue;
            }

            $destinationFile = $file->getRealPath();
            $result = $this->hasher->compareFile($sourceFile, $destinationFile);

            if ($result) {
                $this->log[$this->currentFile->getPathname()] = 'Identical to ' . $destinationFile;

                if ($this->output->isDebug()) {
                    $this->output->writeln($this->currentFile->getPathname() . " is identical to " . $destinationFile);
                }

                return true;
            }
        }

        return false;
    }

    private function renameDestinationFile(string $imageDestinationFilePath)
    {
        $breaker = 10000;

        $destinationFilePath = null;
        do {
            $pathinfo = pathinfo($imageDestinationFilePath);

            $filename = $pathinfo['filename'] . '_' . (10000 - $breaker + 1);

            $destinationFilePath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $pathinfo['extension'];

            if (!$this->filesystem->exists($destinationFilePath)) {
                return $destinationFilePath;
            }

            $breaker--;
        } while ($breaker);

        throw new IOException("It was not possible to find a free rename filename in 100 tries for file: `{$imageDestinationFilePath}`.");
    }

    private function checkFileSize(int $sourceFileSize, int $destinationFileSize)
    {
        $sourceFileMin = $sourceFileSize * 0.9;
        $sourceFileMax = $sourceFileSize * 1.1;

        return $destinationFileSize >= $sourceFileMin && $destinationFileSize <= $sourceFileMax;
    }

    private function writeLogfile()
    {
        if (!count($this->log)) {
            return;
        }

        $result['source'] = $this->sourcePath;
        $result['destination'] = $this->destinationPath;
        $result['created'] = date('r');
        $result['stats'] = [
            'totals' => $this->total,
            'copied' => $this->copied,
            'identical' => $this->identical,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
        $result['log'] = $this->log;

        $logFile = $this->sourcePath . DIRECTORY_SEPARATOR . self::PHOTOSORT_OUTPUT_FILENAME;

        $this->writeJsonFile($logFile, $result);

        if ($this->output->isVerbose()) {
            $this->output->writeln('Result: ' . $logFile);
        }

        $this->log = [];
    }

    private function persistArgs(InputInterface $input)
    {
        $sourcePath = $input->getArgument('source-path');
        $destinationPath = $input->getArgument('destination-path');
        $this->noRename = !!$input->getOption('no-rename');
        $this->monthly = !!$input->getOption('monthly');
        $this->hashFile = $input->getOption('hash-file');

        $this->ensurePathExists($sourcePath);
        $this->ensurePathExists($destinationPath);
        $this->ensureHashFile();

        $this->sourcePath = realpath($sourcePath);
        $this->destinationPath = realpath($destinationPath);
    }

    private function ensurePathExists(?string $directoryPath)
    {
        if (!$this->filesystem->exists($directoryPath)) {
            throw new InvalidArgumentException("The directory `{$directoryPath}` does not exists.");
        }

        if (!is_dir($directoryPath) || !is_readable($directoryPath)) {
            throw new InvalidArgumentException("The directory `{$directoryPath}` is not readable.");
        }
    }

    private function findFiles()
    {
        $finder = Finder::create()
            ->files()
            ->name(self::IMAGES)
            ->in($this->sourcePath);

        if (!$finder->hasResults()) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("Source directory had no image files to process.");
            }

            throw new InvalidArgumentException("The source directory had no files to process.");
        }

        return $finder;
    }

    private function ensureHashFile()
    {
        if (is_null($this->hashFile)) {
            return;
        }

        if (!$this->filesystem->exists($this->hashFile)) {
            throw new InvalidArgumentException("The hash file was not found or not readable");
        }

        $this->hashsOriginal = $this->readJsonFile($this->hashFile);

        foreach ($this->hashsOriginal as $filepath => $items) {
            foreach ($items as $item) {
                $this->hashs[$item] = $filepath;
            }
        }
    }
}