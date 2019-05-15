<?php

namespace RoNoLo\ImageSorter;

class Factory
{
    public function createImageSorter()
    {
        $imageSorter = new ImageSorter(
            $this->createFileMover(),
            $this->createImageDuplicateFinder(),
            $this->createFileSystemHelper()
        );
        $imageSorter->setLogger($this->createLogger());

        return $imageSorter;
    }

    public function createFileMover()
    {
        $fileMover = new FileMover();
        $fileMover->setLogger($this->createLogger());

        return $fileMover;
    }

    public function createImageDuplicateFinder()
    {
        $imageDuplicateFinder = new ImageFileChecker(
            $this->createFileSystemHelper()
        );
        $imageDuplicateFinder->setLogger($this->createLogger());

        return $imageDuplicateFinder;
    }

    private function createFileSystemHelper()
    {
        return new FileSystemHelper();
    }

    private function createLogger()
    {
        return new Logger();
    }
}