<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PhotoSortCommand extends Command
{
    protected static $defaultName = 'photosort:photo-sort';

    private $filesystem;

    private $finder;

    /** @var OutputInterface */
    private $output;

    /** @var \SplFileInfo */
    private $currentFile;

    private $result = [];

    private $errors = 0;

    private $identical = 0;

    private $total = 0;

    private $copied = 0;

    public function __construct(string $name = null)
    {
        $this->filesystem = new Filesystem();
        $this->finder = new Finder();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Copies images into a folder structure');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination-path', InputArgument::REQUIRED, 'Destination directory root');
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

        $this->ensurePathExists($sourcePath);
        $this->ensurePathExists($destinationPath);

        $sourcePath = realpath($sourcePath);
        $destinationPath = realpath($destinationPath);

        $files = $this->finder->files()->name('/\.jpe?g/')->in($sourcePath);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $this->currentFile = $file;

            if ($output->isVerbose()) {
                $output->writeln("Image: " . $file->getBasename());
            }

            $imageDestinationFilePath = $this->buildDestinationPath($destinationPath, $file);

            if ($output->isVeryVerbose()) {
                $output->writeln("Destination Path: " . $imageDestinationFilePath);
            }

            $imageSourceFilePath = $file->getRealPath();

            $this->copyFile($imageSourceFilePath, $imageDestinationFilePath);

            $this->total++;
        }

        $result['source'] = $sourcePath;
        $result['destination'] = $destinationPath;
        $result['created'] = date('r');
        $result['stats'] = [
            'totals' => $this->total,
            'copied' => $this->copied,
            'identical' => $this->identical,
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

    private function buildDestinationPath(string $destination, \SplFileInfo $file)
    {
        $photoDate = $file->getMTime();

        $year = date("Y", $photoDate);
        $yearMonth = date("ym", $photoDate);
        $yearMonthDay = date("ymd", $photoDate);

        $destinationPath = $destination . DIRECTORY_SEPARATOR .
            $year . DIRECTORY_SEPARATOR .
            $yearMonth . DIRECTORY_SEPARATOR .
            $yearMonthDay . DIRECTORY_SEPARATOR .
            $file->getBasename()
        ;

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

    private function copyFile(string $imageSourceFilePath, string $imageDestinationFilePath)
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
        $hashMapCommand = $this->getApplication()->find('photosort:hash-map');

        $dummyHashmapFile = sha1(uniqid(microtime())) . '.json';

        $arguments = [
            'source-path' => $destinationPath,
            '--output-path' => '.' . DIRECTORY_SEPARATOR . $dummyHashmapFile,
        ];

        $input = new ArrayInput($arguments);
        $hashMapCommand->run($input, new NullOutput());

        $sourceFileHash = sha1_file($sourceFile);

        // TODO:
        $json = file_get_contents($dummyHashmapFile);
        $hashmap = json_decode($json, JSON_PRETTY_PRINT);

        unlink($dummyHashmapFile);

        if (isset($hashmap['hashs'][$sourceFileHash])) {
            $this->result[$this->currentFile->getPathname()] = 'identical to ' . implode(', ', $hashmap['hashs'][$sourceFileHash]);

            return true;
        }

        return false;
    }
}