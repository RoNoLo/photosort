<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class DirectoryStructureCheckCommand extends Command
{
    protected static $defaultName = 'tests:directory-structure-check';

    private $filesystem;

    public function __construct(string $name = null)
    {
        $this->filesystem = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Checks if a directory structure and all its files are correct.');
        $this->setHelp('Checks if a test structure if correct');

        $this->addArgument('root-path', InputArgument::REQUIRED, 'Directory root to check');
        $this->addArgument('fixture-file', InputArgument::REQUIRED, 'Fixtures YAML file');
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
        $rootPath = $input->getArgument('root-path');

        $fixtureFile = realpath($fixtureFile);
        $rootPath = realpath($rootPath);

        $this->ensureSourceFile($fixtureFile);
        $this->ensureRootPath($rootPath);

        $data = Yaml::parseFile($fixtureFile);

        $this->ensureDataStructure($data);

        foreach ($data['expected']['structure'] as $expected) {
            $expectedPath = $this->normalizePath($rootPath . DIRECTORY_SEPARATOR . $expected);

            if (!$this->filesystem->exists($expectedPath)) {
                throw new \Exception("The file or path {$expectedPath} does not exists");
            }
        }

        return 0;
    }

    private function ensureSourceFile(?string $sourceFile)
    {
        if (!$this->filesystem->exists($sourceFile)) {
            throw new IOException("The source YAML was not found or file is not accessible.");
        }
    }

    private function ensureRootPath(?string $resourcesPath)
    {
        if (!$this->filesystem->exists($resourcesPath)) {
            throw new IOException("The resource directory does not exist or is not accessible.");
        }
    }

    private function normalizePath($path) {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function ensureDataStructure(&$data)
    {
        if (!isset($data['expected']['structure'])) {
            throw new \Exception("The fixtures YAML files does not contains an `expected/structure` section.");
        }
    }
}