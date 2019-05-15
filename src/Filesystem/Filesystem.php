<?php

namespace RoNoLo\PhotoSort\Filesystem;

use RoNoLo\PhotoSort\Iterator\ExtensionFilterIterator;
use RoNoLo\PhotoSort\Iterator\ExtensionRecursiveFilterIterator;
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
            $fsi = new \FilesystemIterator($targetDir, $flags);
            // No extension filtered
            if (!count($extensions)) {
                return $fsi;
            }

            return new ExtensionFilterIterator($fsi, $extensions);
        }

        $rdi = new \RecursiveDirectoryIterator($targetDir, $flags);
        if (!count($extensions)) {
            $rii = new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::CHILD_FIRST);

            return $rii;
        }

        $erfi = new ExtensionRecursiveFilterIterator($rdi, $extensions);

        $foo = iterator_to_array($erfi);

        $rii = new \RecursiveIteratorIterator($erfi, \RecursiveIteratorIterator::CHILD_FIRST);

        return $rii;
    }
}