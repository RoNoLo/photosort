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

        if (!$file->isReadable()) {
            throw new IOException("Could not read file content.");
        }

        return sha1_file($file->getRealPath());
    }

    public function files($targetDir, $recursive = false, array $extensions = [])
    {
        $targetDir = rtrim($targetDir, '/\\');

        // Iterate in destination folder to remove obsolete entries
        if (!$this->exists($targetDir)) {
            throw new IOException('The directory does not exists.');
        }

        $flags = \FilesystemIterator::SKIP_DOTS;

        // not recursive
        if (!$recursive) {
            // No extension filtered
            if (!count($extensions)) {
                return new \FilesystemIterator($targetDir, $flags);
            }

            return new \ExtensionFilterIterator(
                new \FilesystemIterator($targetDir, $flags),
                $extensions
            );
        }

        if (!count($extensions)) {
            return new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($targetDir, $flags),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        }

        return new \RecursiveIteratorIterator(
            new \ExtensionRecursiveFilterIterator(
                new \RecursiveDirectoryIterator($targetDir, $flags),
                $extensions
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }
}