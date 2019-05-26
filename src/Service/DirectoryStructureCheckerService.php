<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class DirectoryStructureCheckerService
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function check(string $fixtureFile, string $rootPath): bool
    {
        $fixtureFile = realpath($fixtureFile);
        $rootPath = realpath($rootPath);

        $this->ensureSourceFile($fixtureFile);
        $this->ensureRootPath($rootPath);

        $data = Yaml::parseFile($fixtureFile);

        $this->ensureDataStructure($data);

        // This checks the file data against the directory structure
        foreach ($data['expected']['structure'] as $expected) {
            $expectedPath = $this->normalizePath($rootPath . DIRECTORY_SEPARATOR . $expected);

            if (!$this->filesystem->exists($expectedPath)) {
                throw new \Exception("The file or path {$expectedPath} does not exists");
            }
        }

        // this will test the directory structure against the file
        $finder = Finder::create()->files()->in($rootPath);

        foreach ($finder as $file) {
            if (!in_array($this->unixPath($file->getRelativePathname()), $data['expected']['structure'])) {
                throw new \Exception("The file " . $file->getRelativePathname() . " was found, but is not expected in the structure.");
            }
        }

        return true;
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

    private function unixPath($path)
    {
        return str_replace(['/', '\\'], '/', $path);
    }
}