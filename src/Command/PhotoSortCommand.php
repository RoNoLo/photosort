<?php

namespace App\Command;

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

    private $filesystem;

    /** @var OutputInterface */
    private $output;

    /** @var \SplFileInfo */
    private $currentFile;

    private $result = [];

    private $errors = 0;

    private $identical = 0;

    private $total = 0;

    private $copied = 0;

    private $skipped = 0;

    public function __construct(string $name = null)
    {
        $this->filesystem = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Copies images into a folder structure');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination-path', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('not-rename-and-copy-duplicates', null, InputOption::VALUE_OPTIONAL, 'Rename images which have the same name, but are not identical', true);
        $this->addOption('monthly', null, InputOption::VALUE_OPTIONAL, 'Sort only YY/YYMM/images instead of YY/YYMM/YYMMDD/images.', false);
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

        $this->ensurePathExists($sourcePath);
        $this->ensurePathExists($destinationPath);

        $sourcePath = realpath($sourcePath);
        $destinationPath = realpath($destinationPath);

        $finder = new Finder();

        $finder->files()->name('/\.jpe?g/')->in($sourcePath);

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

            $this->currentFile = $file;

            if ($output->isVerbose()) {
                $output->writeln("Image: " . $file->getBasename());
            }

            $imageDestinationFilePath = $this->buildDestinationPath($destinationPath, $file, $monthly);

            if ($output->isVeryVerbose()) {
                $output->writeln("Destination Path: " . $imageDestinationFilePath);
            }

            $imageSourceFilePath = $file->getRealPath();

            $this->copyFile($imageSourceFilePath, $imageDestinationFilePath, $notRenameDuplicates);

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
        $result['log'] = $this->result;
        $result['log'] = $this->result;

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

    private function copyFile(string $imageSourceFilePath, string $imageDestinationFilePath, $notRenameDuplicates = false)
    {
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
                $this->skipped++;
                return;
            }

            $imageDestinationFilePath = $this->renameDestinationFile($imageDestinationFilePath);
        }

        // Copy the file
        try {
            $this->filesystem->copy($imageSourceFilePath, $imageDestinationFilePath);

            $this->result[$this->currentFile->getPathname()] = 'copied to ' . $imageDestinationFilePath;
            $this->copied++;
        } catch (IOException $e) {
            $this->errors++;
            $this->result[$this->currentFile->getPathname()] = 'error on copy ' . $e->getMessage();
        }
    }

    private function checkIdentical($sourceFile, $destinationFile)
    {
        $sourceFileHash = sha1_file($sourceFile);
        $destinationFileHash = sha1_file($destinationFile);

        if ($sourceFileHash === $destinationFileHash) {
            $this->result[$this->currentFile->getPathname()] = 'identical to ' . $destinationFile;

            return true;
        }

        return false;
    }

    private function checkIdenticalInPath(string $sourceFile, string $destinationPath)
    {
        $finder = new Finder();

        $finder->files()->name('/\.jpe?g/')->in($destinationPath);

        $hashs = [];
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if ($file->isDir()) {
                continue;
            }

            $path = $file->getRealPath();
            $hash = sha1_file($file);

            if ($file->getSize() === 0) {
                continue;
            }

            $hashs[$hash][] = $path;
        }

        $sourceFileHash = sha1_file($sourceFile);

        if (isset($hashs[$sourceFileHash])) {
            $this->result[$this->currentFile->getPathname()] = 'identical to ' . implode(', ', $hashs[$sourceFileHash]);

            return true;
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
}