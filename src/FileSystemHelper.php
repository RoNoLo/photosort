<?php

namespace RoNoLo\ImageSorter;

use SplFileInfo;

class FileSystemHelper
{
    private $fileExtensionsRegEx = "/^jpe?g$/i";

    public function getFileList($path)
    {
        $list = [];
        $files = new \RecursiveDirectoryIterator($path);
        $files = new \RecursiveIteratorIterator($files);
//        $files = iterator_to_array($files);
        /** @var SplFileInfo $fileInfo */
        foreach ($files as $path => $fileInfo) {
            if ($fileInfo->isDir()) {
                continue;
            }
            if (!preg_match($this->fileExtensionsRegEx, $fileInfo->getExtension())) {
                continue;
            }

            $list[$fileInfo->getPathname()] = $fileInfo->getMTime();
        }

        return $list;
    }

    public function getDirectoryList($path)
    {
        $list = [];
        foreach (new \DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            if ($fileInfo->isFile()) {
                continue;
            }

            $list[] = $fileInfo->getPathname();
        }

        return $list;
    }

    public function findPossibleDirectoryCandidates($directories, $yearMonthDay)
    {
        $list = [];
        foreach ($directories as $directory) {
            $baseName = basename($directory);
            if (strpos($baseName, $yearMonthDay) === 0) {
                $list[] = $directory;
            }
        }

        return $list;
    }
}