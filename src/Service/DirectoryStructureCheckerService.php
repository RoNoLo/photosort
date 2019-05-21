<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class DirectoryStructureCheckerService
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    protected function check(string $fixtureFile, string $rootPath)
    {
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