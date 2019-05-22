<?php

namespace App\Service;

use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use Symfony\Component\Finder\Finder;

class HashService
{
    public function hashFile(string $filePath, $imageHash = false)
    {
        $this->ensureFileExists($filePath);

        $filePath = realpath($filePath);

        $this->ensureSupportedImage($filePath, $imageHash);

        $hashs = [];

        $hashs['sha1'] = sha1_file($filePath);

        if ($imageHash) {
            $differenceHasher = new ImageHash(new DifferenceHash(4));
            $averageHasher = new ImageHash(new AverageHash(4));
            // $blockHasher = new ImageHash(new BlockHash());
            $perceptualHasher = new ImageHash(new PerceptualHash(16));

            $hashs['difference'] = $differenceHasher->hash($filePath)->toHex();
            $hashs['average'] = $averageHasher->hash($filePath)->toHex();
            $hashs['perceptual'] = $perceptualHasher->hash($filePath)->toHex();
        }

        return $hashs;
    }

    public function hashFiles(Finder $files, $imageHash = false)
    {
        $hashs = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            $hashs[$filePath] = $this->hashFile($filePath, $imageHash);
        }

        return $hashs;
    }

    public function compareFile(string $filePath, string $otherFilePath, $imageHash = false, $maxAvgDistance = 2, $maxDistance = 3): bool
    {
        $fileHash = $this->hashFile($filePath, $imageHash);
        $otherHash = $this->hashFile($otherFilePath, $imageHash);

        return $this->compareHashResults($fileHash, $otherHash, $maxAvgDistance, $maxDistance);
    }

    public function compareHashResults(array $hashsA, array $hashsB, $maxAvgDistance = 2, $maxDistance = 3): bool
    {
        // The same sha1 beats everything
        if ($hashsA['sha1'] === $hashsB['sha1']) {
            return true;
        }

        $imageHashs = ['difference', 'average', 'perceptual'];

        $compare = [];
        foreach ($imageHashs as $imageHash) {
            if (isset($hashsA[$imageHash]) && isset($hashsB[$imageHash])) {
                $this->ensureHash($hashsA[$imageHash]);
                $this->ensureHash($hashsB[$imageHash]);

                $compare[$imageHash] = Hash::fromHex($hashsA[$imageHash])->distance(Hash::fromHex($hashsB[$imageHash]));
            }
        }

        if (count($compare)) {
            if (max($compare) > $maxDistance) {
                return false;
            }

            return (array_sum($compare) / count($compare)) <= $maxAvgDistance;
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

    private function ensureHash($hash)
    {
        if (trim($hash) === "") {
            throw new \Exception("An empty hash was found, which is unsupported.");
        }
    }
}