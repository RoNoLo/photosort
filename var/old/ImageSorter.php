<?php

namespace RoNoLo\ImageSorter;

/**
 * Will sort, compare and copy images.
 */

use Psr\Log\LoggerAwareTrait;

class ImageSorter
{
    private $sourcePath;

    private $destinationPath;

    private $currentFilePath;

    private $debug = 1;

    /** @var FileSystemHelper */
    private $fileSystemHelper;

    /** @var ImageFileChecker */
    private $imageDuplicateFinder;

    /** @var FileMover */
    private $fileMover;

    use LoggerAwareTrait;

    /**
     * ImageSorter constructor.
     * @param FileMover $fileMover
     * @param ImageFileChecker $imageDuplicateFinder
     * @param FileSystemHelper $fileSystemHelper
     */
    public function __construct(
        FileMover $fileMover,
        ImageFileChecker $imageDuplicateFinder,
        FileSystemHelper $fileSystemHelper
    ) {
        $this->fileMover = $fileMover;
        $this->imageDuplicateFinder = $imageDuplicateFinder;
        $this->fileSystemHelper = $fileSystemHelper;
    }

    public function run()
    {
        try {
            $this->checkArguments();
            $this->execute();
            $this->log("Ende");
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo "\n";
            $this->showHelp();
        }
    }

    private function checkArguments()
    {
        $shortopt = "s:d:x";
        $longopts = [
            "source:",
            "destination:",
            "debug"
        ];

        $options = getopt($shortopt, $longopts);

        if (isset($options['s']) || isset($options['source'])) {
            $sourcePath = isset($options['s']) ? trim($options['s']) : trim($options['source']);
            $this->ensurePathExists($sourcePath);
            $this->sourcePath = $sourcePath;
        } else {
            throw new \InvalidArgumentException("Source-Path missing");
        }

        if (isset($options['d']) || isset($options['destination'])) {
            $destinationPath = isset($options['d']) ? trim($options['d']) : trim($options['destination']);
            $this->ensurePathExists($destinationPath);
            $this->destinationPath = $destinationPath;
        } else {
            throw new \InvalidArgumentException("Destination-Path missing");
        }
    }

    private function ensurePathExists($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Path not found: " . $path);
        }
    }

    private function showHelp()
    {
        echo "php image_sort.php --source /source/path --destination /destination/path";
        echo "\n";
    }

    private function execute()
    {
        $files = $this->fileSystemHelper->getFileList($this->sourcePath);

        foreach ($files as $filePath => $timestamp) {
            $this->log($filePath . ' - Date: ' . date("Y-m-d H:i:s", $timestamp));

            $this->currentFilePath = $filePath;

            $this->checkForDestinationFile($filePath, $timestamp);
        }
    }

    private function checkForDestinationFile($filePath, $timestamp)
    {
        $year = date("Y", $timestamp);
        $yearMonth = date("ym", $timestamp);
        $yearMonthDay = date("ymd", $timestamp);

        $yearPath = $this->destinationPath . DIRECTORY_SEPARATOR . $year;
        $yearMonthPath = $yearPath . DIRECTORY_SEPARATOR . $yearMonth;
        $destinationPath = $yearMonthPath . DIRECTORY_SEPARATOR . $yearMonthDay;

        // There is no year directory
        if (!file_exists($yearPath)) {
            $this->log("FilePath: " . $yearPath . " not found");

            $this->fileMover->moveFile($filePath, $destinationPath);
            return;
        }

        // There is no year/month directory
        if (!file_exists($yearMonthPath)) {
            $this->log("FilePath: " . $yearMonthPath . " not found");

            $this->fileMover->moveFile($filePath, $destinationPath);
            return;
        }

        // Now I need a list of directories and see if any of them starts with my date
        $directories = $this->fileSystemHelper->getDirectoryList($yearMonthPath);

        if (!count($directories)) {
            $this->log("No directorys below: " . $yearMonthPath . " found");

            $this->fileMover->moveFile($filePath, $destinationPath);
            return;
        }

        $candidates = $this->fileSystemHelper->findPossibleDirectoryCandidates($directories, $yearMonthDay);

        if (!count($candidates)) {
            $this->log("No directory candidates below: " . $yearMonthPath . " found");
            // Do something
        }

        if (!$this->imageDuplicateFinder->checkForExistingFileInDirectories($filePath, $candidates)) {
            // We now can create a directory and put the file there
            $destinationPath = $yearMonthPath . DIRECTORY_SEPARATOR . $yearMonthDay;

            $this->fileMover->moveFile($filePath, $destinationPath);
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