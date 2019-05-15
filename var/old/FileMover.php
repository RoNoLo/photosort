<?php

namespace RoNoLo\ImageSorter;

use Psr\Log\LoggerAwareTrait;

class FileMover
{
    use LoggerAwareTrait;

    private $debug = 1;

    public function moveFile($sourcePath, $destinationPath)
    {
        if (!file_exists($sourcePath)) {
            return;
        }

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $destinationFilePath = $destinationPath . DIRECTORY_SEPARATOR . basename($sourcePath);

        if (!file_exists($destinationFilePath)) {
            if (copy($sourcePath, $destinationFilePath)) {
                $this->log("Copy File: " . $sourcePath . " to: " . $destinationFilePath);
                if (file_exists($destinationFilePath)) {
                    $this->log("Delete Original: " . $sourcePath);
                    unlink($sourcePath);
                } else {
                    $this->log("Copy'ed File: " . $destinationFilePath . " not found after copy!!");
                }
            } else {
                $this->log("Copy File: " . $sourcePath . " to: " . $destinationFilePath . " was not successful.");
            }
        } else {
            $this->log("File: " . $destinationFilePath . " already exists! Will not copy " . $sourcePath . " over");
        }
    }

    private function log($string)
    {
        if ($this->debug) {
            echo $string . "\n";
            flush();
        }
    }
}