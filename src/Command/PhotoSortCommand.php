<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class PhotoSortCommand extends Command
{
    protected static $defaultName = 'photosort:photo-sort';

    private $filesystem;

    private $finder;

    /** @var OutputInterface */
    private $output;

    public function __construct(string $name = null)
    {
        $this->filesystem = new Filesystem();
        $this->finder = new Finder();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Copies or moves images into a folder structure');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('copy', 'c', InputOption::VALUE_OPTIONAL, 'Copy files instead of moving', false);
        $this->addOption('use-hashmap', 'h', InputOption::VALUE_OPTIONAL, 'Use hashmap file to find duplicates', false);
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

        try {
            $source = $input->getArgument('source');
            $destination = $input->getArgument('destination');
            
            $this->ensurePathExists($source);
            $this->ensurePathExists($destination);

            $source = realpath($source);
            $destination = realpath($destination);
            
            $copy = !!$input->getOption('copy');
            $useHashMap = !!$input->getOption('use-hashmap');
            
            $files = $this->finder->files()->name('/\.jpe?g/')->in($source);
            
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if ($output->isVerbose()) {
                    $output->writeln("Image: " . $file->getBasename());
                }

                $imageDestinationPath = $this->buildDestinationPath($destination, $file);

                if ($output->isVeryVerbose()) {
                    $output->writeln("Destination Path: " . $imageDestinationPath);
                }

                $this->moveFile($file, $imageDestinationPath, $copy);
            }
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage());
        }
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

    private function moveFile(\SplFileInfo $file, string $imageDestinationFilePath, $copyOnly = false)
    {
        if ($this->checkForAlreadyExistingIdenticalFile($file, $imageDestinationFilePath)) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("Note: Image: `{$file->getBasename()}` already at destination found.");
            }

            if (!$copyOnly) {
                $this->filesystem->remove($file->getRealPath());
                if ($this->output->isVerbose()) {
                    $this->output->writeln("Note: Image: `{$file->getBasename()}` removed.");
                }
            }
        }
        

        $imageDestinationPath = dirname($imageDestinationFilePath);

        if (!$this->filesystem->exists($imageDestinationPath)) {
            $this->filesystem->mkdir($imageDestinationPath);
        }

        if ($this)
    }

    private function checkForAlreadyExistingIdenticalFile(\SplFileInfo $file, string $imageDestinationFilePath)
    {
        if (!$this->filesystem->exists($imageDestinationFilePath)) {
            return false;
        }

        $destinationFileHash = sha1_file($imageDestinationFilePath);
        $sourceFileHash = sha1_file($file->getRealPath());

        return $sourceFileHash === $destinationFileHash;
    }
}