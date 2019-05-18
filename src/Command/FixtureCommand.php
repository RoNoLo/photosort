<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class FixtureCommand extends Command
{
    protected static $defaultName = 'tests:fixture';

    private $filesystem;

    public function __construct(string $name = null)
    {
        $this->filesystem = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Creates Fixtures');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('fixture-file', InputArgument::REQUIRED, 'Fixtures YAML file');
        $this->addArgument('resource-path', InputArgument::REQUIRED, 'Directory with the resources');
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
        $fixtureFile = $input->getArgument('fixture-file');
        $resourcesPath = $input->getArgument('resource-path');
        $destinationPath = $input->getArgument('destination-path');

        $fixtureFile = realpath($fixtureFile);
        $resourcesPath = realpath($resourcesPath);
        $destinationPath = realpath($destinationPath);

        $this->ensureSourceFile($fixtureFile);
        $this->ensureResourcePath($resourcesPath);
        $this->ensureDestinationPath($destinationPath);

        $data = Yaml::parseFile($fixtureFile);

        foreach ($data['fixtures'] as $fixtureFile => $destinationFiles) {
            foreach ($destinationFiles as $destinationFile => $datetime) {
                $sourceFilePath = realpath($this->normalizePath($resourcesPath . DIRECTORY_SEPARATOR . $fixtureFile));

                if (!$this->filesystem->exists($sourceFilePath)) {
                    throw new IOException("The source file `{$sourceFilePath} does not exists.");
                }

                $destinationFilePath = $this->normalizePath($destinationPath . DIRECTORY_SEPARATOR . $destinationFile);

                $this->filesystem->copy($sourceFilePath, $destinationFilePath);

                if (!is_null($datetime)) {
                    $this->filesystem->touch($destinationFilePath, $datetime);
                }
            }
        }
    }

    private function ensureSourceFile(?string $sourceFile)
    {
        if (!$this->filesystem->exists($sourceFile)) {
            throw new IOException("The source YAML was not found or file is not accessible.");
        }
    }

    private function ensureDestinationPath(?string $directoryPath)
    {
        if (!$this->filesystem->exists($directoryPath)) {
            throw new IOException("The destination directory does not exist or is not accessible.");
        }
    }

    private function ensureResourcePath(?string $resourcesPath)
    {
        if (!$this->filesystem->exists($resourcesPath)) {
            throw new IOException("The resource directory does not exist or is not accessible.");
        }
    }

    private function normalizePath($path) {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}