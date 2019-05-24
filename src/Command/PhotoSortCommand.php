<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PhotoSortCommand extends Command
{
    protected static $defaultName = 'photosort:photo-sort';

    const IMAGES = ['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'];

    private $filesystem;

    /** @var OutputInterface */
    private $output;

    /** @var \SplFileInfo */
    private $currentFile;

    private $log = [];

    private $errors = 0;

    private $identical = 0;

    private $total = 0;

    private $copied = 0;

    private $skipped = 0;

    /** @var HashService */
    private $hasher;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->filesystem = $filesystem;
        $this->hasher = $hashService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Copies images into a folder structure YY/YYMM/YYMMDD/files');
        $this->setHelp("This command will copy images into a folder structure based on the file date of the image.\nA log file (photosort_log.json) will be created in the source directory to see if everything went well.");

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination-path', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('not-rename-and-copy-duplicates', null, InputOption::VALUE_OPTIONAL, 'Rename images which have the same name, but are not identical', false);
        $this->addOption('monthly', null, InputOption::VALUE_OPTIONAL, 'Sort only YY/YYMM/images instead of YY/YYMM/YYMMDD/images', false);
        $this->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'No copy, just the log what would be done', false);
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

        $sourcePath = $input->getArgument('source-path');
        $destinationPath = $input->getArgument('destination-path');
        $notRenameDuplicates = !!$input->getOption('not-rename-and-copy-duplicates');
        $monthly = !!$input->getOption('monthly');
        $dryRun = !!$input->getOption('dry-run');

        $this->ensurePathExists($sourcePath);
        $this->ensurePathExists($destinationPath);

        $sourcePath = realpath($sourcePath);
        $destinationPath = realpath($destinationPath);

        $finder = new Finder();

        $finder->files()->name(self::IMAGES)->in($sourcePath);

        if (!$finder->hasResults()) {
            if ($output->isVerbose()) {
                $output->writeln("Source directory had no image files to process.");
            }

            return 0;
        }

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if ($file->isDir()) {
                continue;
            }

            if ($file->getSize() === 0) {
                $this->log[$this->currentFile->getPathname()] = 'skipped because the filesize was 0 bytes';
                $this->skipped++;
            }

            $this->currentFile = $file;

            if ($output->isVerbose()) {
                $output->writeln("Image: " . $file->getBasename());
            }

            $imageDestinationFilePath = $this->buildDestinationPath($destinationPath, $file, $monthly);

            if ($output->isVeryVerbose()) {
                $output->writeln("Destination Path: " . $imageDestinationFilePath);
            }

            $imageSourceFilePath = $file->getRealPath();

            $this->copyFile($imageSourceFilePath, $imageDestinationFilePath, $notRenameDuplicates, $dryRun);

            $this->total++;
        }

        $result['source'] = $sourcePath;
        $result['destination'] = $destinationPath;
        $result['created'] = date('r');
        $result['stats'] = [
            'totals' => $this->total,
            'copied' => $this->copied,
            'identical' => $this->identical,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
        $result['log'] = $this->log;

        $logFile = $sourcePath . DIRECTORY_SEPARATOR . 'photosort_log.json';

        $this->filesystem->dumpFile($logFile, json_encode($result, JSON_PRETTY_PRINT));

        if ($output->isVerbose()) {
            $output->writeln('Result: ' . $logFile);
        }

        return $result;
    }

    private function buildDestinationPath(string $destination, \SplFileInfo $file, $monthly = false)
    {
        $photoDate = $file->getMTime();

        $year = date("Y", $photoDate);
        $yearMonth = date("ym", $photoDate);
        $yearMonthDay = date("ymd", $photoDate);

        if ($monthly) {
            $destinationPath = $destination . DIRECTORY_SEPARATOR .
              $year . DIRECTORY_SEPARATOR .
              $yearMonth . DIRECTORY_SEPARATOR .
              $file->getBasename()
            ;
        } else {
            $destinationPath = $destination . DIRECTORY_SEPARATOR .
                $year . DIRECTORY_SEPARATOR .
                $yearMonth . DIRECTORY_SEPARATOR .
                $yearMonthDay . DIRECTORY_SEPARATOR .
                $file->getBasename()
            ;
        }

        return $destinationPath;
    }

    private function ensurePathExists(?string $directoryPath)
    {
        if (!$this->filesystem->exists($directoryPath)) {
            throw new IOException("The directory `{$directoryPath}` does not exists.");
        }

        if (!is_dir($directoryPath) || !is_readable($directoryPath)) {
            throw new IOException("The directory `{$directoryPath}` is not readable.");
        }
    }

    private function copyFile(string $imageSourceFilePath, string $imageDestinationFilePath, $notRenameDuplicates = false, $dryRun = false)
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
            if ($notRenameDuplicates) {
                $this->log[$this->currentFile->getPathname()] = 'skipped because a file with identical name, but different content, was already at destination ' . $imageDestinationFilePath;
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
            if (!$dryRun) {
                $this->filesystem->copy($imageSourceFilePath, $imageDestinationFilePath);
            }

            $this->log[$this->currentFile->getPathname()] = 'copied to ' . $imageDestinationFilePath;
            $this->copied++;

            if ($this->output->isVerbose()) {
                $this->output->writeln("copied from: " . $this->currentFile->getPathname() . " to: ". $imageDestinationFilePath);
            }
        } catch (IOException $e) {
            $this->errors++;
            $this->log[$this->currentFile->getPathname()] = 'error on copy ' . $e->getMessage();
        }
    }

    private function checkIdentical($sourceFile, $destinationFile)
    {
        $result = $this->hasher->compareFile($sourceFile, $destinationFile);

        if ($result === 0) {
            $this->log[$this->currentFile->getPathname()] = 'identical to ' . $destinationFile;

            if ($this->output->isDebug()) {
                $this->output->writeln($this->currentFile->getPathname() . " is identical to " . $destinationFile);
            }

            return true;
        }

        if ($result <= 3) {
            if ($this->output->isVeryVerbose()) {
                $this->output->writeln($this->currentFile->getPathname() . " is nearly identical to " . $destinationFile . " Score({$result})");
            }
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

            if ($result === 0) {
                $this->log[$this->currentFile->getPathname()] = 'identical to ' . $destinationFile;

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
}