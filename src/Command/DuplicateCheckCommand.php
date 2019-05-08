<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class DuplicateCheckCommand extends Command
{
    protected static $defaultName = 'find-duplicates';

    private $fs;

    private $counter = 0;

    private $result = [];

    public function __construct(string $name = null)
    {
        $this->fs = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Finds duplicate files');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('delete-duplicates-from-source', 'd', InputOption::VALUE_OPTIONAL, 'Delete files were duplicates in destination folder sturcture were found', false);
        $this->addOption('result-file', 'rf', InputOption::VALUE_OPTIONAL, 'Name of the result file', 'photosort_duplicates.json');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');

        $this->ensureSource($source);
        $this->ensureDestination($destination);

        $delete = !!$input->getOption('delete-duplicates-from-source');
        $resultFile = $input->getOption('result-file');

        $files = $this->fs->files($source, true);

        $this->ensureHashMaps($destination, $output);

        $json = file_get_contents($destination . DIRECTORY_SEPARATOR . 'photosort_hash2path.json');
        $array = json_decode($json, JSON_PRETTY_PRINT);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $hash = $this->fs->hash($file);

            if (isset($array[$hash])) {
                $this->performDuplicateFile($file, $array[$hash], $hash);
            }
        }

        $this->writeResultFile($source, $resultFile);
    }

    private function ensureSource(?string $directoryPath)
    {
        if (!$this->fs->exists($directoryPath)) {
            throw new IOException("The source directory does not exist or is not accessible.");
        }
    }

    private function ensureDestination(?string $directoryPath)
    {
        if (!$this->fs->exists($directoryPath)) {
            throw new IOException("The destination directory does not exist or is not accessible.");
        }
    }

    private function ensureHashMaps($destinationPath, $output)
    {
        if (!$this->fs->exists($destinationPath .  DIRECTORY_SEPARATOR . 'photosort_hash2path.json')) {
            $hashMapCommand = $this->getApplication()->find('hash-map');

            $arguments = [
                'source' => $destinationPath,
            ];

            $input = new ArrayInput($arguments);
            $hashMapCommand->run($input, $output);
        }
    }

    private function performDuplicateFile(\SplFileInfo $file, array $destinationFilePath, string $hash)
    {
        $this->counter++;

        $this->result[$file->getRealPath()][$hash] = $destinationFilePath;
    }

    private function writeResultFile($sourceFilePath, $resultFile)
    {
        $this->fs->dumpFile($sourceFilePath . DIRECTORY_SEPARATOR . $resultFile, json_encode($this->result, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
    }
}