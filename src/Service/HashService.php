<?php

namespace App\Service;

use Imagick;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class HashService
{
    private $imageHashsEnabled = false;

    public function __construct()
    {
        $this->ensureImagickExtension();
    }

    public function hashFile(string $filePath, $imageHash = false)
    {
        $this->ensureFileExists($filePath);

        $filePath = realpath($filePath);

        $hashs = [];
        $hashs['sha1'] = sha1_file($filePath);

        if ($imageHash && $this->imageHashsEnabled) {
            $this->ensureSupportedImage($filePath, $imageHash);

            $imagick = new Imagick($filePath);
            $signature = $imagick->getImageSignature();

            $hashs['signature'] = $signature;
        }

        return $hashs;
    }

    public function hashFiles(Finder $files, $imageHash = false)
    {
        $hashs = [];

        /** @var \SplFileInfo $file */
        $i = 0;
        foreach ($files as $file) {
            $i++;
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            $hashs[$filePath] = $this->hashFile($filePath, $imageHash);
        }

        return $hashs;
    }

    public function compareFile(string $filePath, string $otherFilePath, $imageHash = false): bool
    {
        $fileHash = $this->hashFile($filePath, $imageHash);
        $otherHash = $this->hashFile($otherFilePath, $imageHash);

        return $this->compareHashResults($fileHash, $otherHash);
    }

    public function compareHashResults(array $hashsA, array $hashsB): bool
    {
        // The same sha1 beats everything
        if ($hashsA['sha1'] === $hashsB['sha1']) {
            return true;
        }

        if (isset($hashsA['signature']) && isset($hashsB['signature'])) {
            if ($hashsA['signature'] === $hashsB['signature']) {
                return true;
            }
        }

        return false;
    }

    private function ensureFileExists(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("The file `{$filePath}` does not exists.");
        }

        $filePath = realpath($filePath);

        if (!is_file($filePath)) {
            throw new \Exception("The path `{$filePath}` is not a file.");
        }
    }

    private function ensureSupportedImage(string $filePath, bool $imageHash)
    {
        if (!$imageHash) {
            return;
        }

        $result = getimagesize($filePath);

        if (!in_array($result[2], [IMG_GIF, IMG_JPG, IMG_PNG])) {
            throw new \Exception("The image type is not supported for hashing.");
        }
    }

    private function ensureImagickExtension()
    {
        if (extension_loaded('imagick')) {
            $this->imageHashsEnabled = true;
        }
    }
}