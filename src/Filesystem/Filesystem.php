<?php

namespace RoNoLo\PhotoSort\Filesystem;

use Symfony\Component\Filesystem\Exception\IOException;

class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    public function hash(\SplFileInfo $file)
    {
        if ($file->isDir()) {
            throw new IOException('A directory cannot be hashed.');
        }

        $contents = @file_get_contents($file->getRealPath());

        if (empty($contents)) {
            throw new \Exception("File was empty");
        }

        return sha1($contents);
    }

    public function files($targetDir, $recursive = false)
    {
        $targetDir = rtrim($targetDir, '/\\');

        // Iterate in destination folder to remove obsolete entries
        if (!$this->exists($targetDir)) {
            throw new IOException('The directory does not exists.');
        }

        $flags = \FilesystemIterator::SKIP_DOTS;
        if ($recursive) {
            return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetDir, $flags), \RecursiveIteratorIterator::CHILD_FIRST);
        }

        return new \FilesystemIterator($targetDir, $flags);
    }
}