<?php

namespace RoNoLo\ImageSorter;

use Psr\Log\LoggerAwareTrait;

class ImageFileChecker
{
    use LoggerAwareTrait;

    private $debug = 1;

    private $sha1Cache = [];

    /** @var FileSystemHelper */
    private $fileSystemHelper;

    public function __construct(FileSystemHelper $fileSystemHelper)
    {
        $this->fileSystemHelper = $fileSystemHelper;
    }

    public function checkForExistingFileInDirectories($filePath, $directories)
    {
        $found = false;
        $sha1SourceFile = $this->getSha1ForFilepath($filePath);
        foreach ($directories as $directory) {
            $files = $this->fileSystemHelper->getFileList($directory);
            $files = array_keys($files);

            foreach ($files as $path) {
                $sha1DestinationFile = $this->getSha1ForFilepath($path);
                if ($this->checkIfContentIsTheSame($filePath, $sha1SourceFile, $sha1DestinationFile, $path)) {
                    return true;
                }
            }
        }

        return $found;
    }

    private function checkIfContentIsTheSame($filePath, $sha1SourceFile, $sha1DestinationFile, $path)
    {
        if ($sha1SourceFile == $sha1DestinationFile) {
            // Files are identical, we could delete the sourceFile
            $this->log("File " . $filePath . " is identical to " . $path);
            $this->log("Deleting: " . $filePath);
            // WARNING!!
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return true;
        }
        return false;
    }

    private function getSha1ForFilepath($filePath)
    {
        if (!array_key_exists($filePath, $this->sha1Cache)) {
            $this->sha1Cache[$filePath] = sha1_file($filePath);
        }

        return $this->sha1Cache[$filePath];
    }

    private function log($string)
    {
        if ($this->debug) {
            echo $string . "\n";
            flush();
        }
    }
}