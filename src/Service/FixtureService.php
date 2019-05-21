<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class FixtureService
{
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $fixtureFile
     * @param string $resourcesPath
     * @param string $destinationPath
     *
     * @return void
     */
    protected function execute(string $fixtureFile, string $resourcesPath, string $destinationPath)
    {
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