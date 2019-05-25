<?php

namespace App\Service;

use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class HashService
{
    const HASH_IMAGE_RESIZE_AUTO = -1;
    const HASH_IMAGE_RESIZE_DEFAULT = 1;
    const HASH_IMAGE_RESIZE_DOUBLE = 2;
    const HASH_IMAGE_RESIZE_QUADRUPLE = 4;
    const HASH_IMAGE_RESIZE_OCTUPLE = 8;
    const HASH_IMAGE_RESIZE_SEXDECUPLE = 16;
    const HASH_IMAGE_RESIZE_DUOTRIGUPLE = 32;

    /** @var OutputInterface */
    private $output;

    public function __construct(OutputInterface $output = null)
    {
        $this->output = $output;
    }

    public function hashFile(string $filePath, $imageHash = false, $hashQuality = self::HASH_IMAGE_RESIZE_AUTO)
    {
        $this->ensureFileExists($filePath);

        $filePath = realpath($filePath);

        $hashs = [];
        $hashs['sha1'] = sha1_file($filePath);

        if ($imageHash) {
            $this->ensureSupportedImage($filePath, $imageHash);

            $hashQuality = $this->fetchHashQualityByImage($filePath, $hashQuality);

            $differenceHasher = new ImageHash(new DifferenceHash(8 * $hashQuality));
            $averageHasher = new ImageHash(new AverageHash(8 * $hashQuality));
            $perceptualHasher = new ImageHash(new PerceptualHash(32 * $hashQuality));

            $hashs['difference'] = $differenceHasher->hash($filePath)->toHex();
            $hashs['average'] = $averageHasher->hash($filePath)->toHex();
            $hashs['perceptual'] = $perceptualHasher->hash($filePath)->toHex();
        }

        return $hashs;
    }

    public function hashFiles(Finder $files, $imageHash = false, $hashQuality = self::HASH_IMAGE_RESIZE_AUTO)
    {
        $hashs = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            if ($this->output instanceof OutputInterface) {
                if ($this->output->isVerbose()) {
                    $this->output->writeln("Hashing file: " . $filePath . " ...");
                }
            }

            $hashs[$filePath] = $this->hashFile($filePath, $imageHash, $hashQuality);
        }

        return $hashs;
    }

    public function compareFile(string $filePath, string $otherFilePath, $imageHash = false, $hashQuality = self::HASH_IMAGE_RESIZE_AUTO): int
    {
        $fileHash = $this->hashFile($filePath, $imageHash, $hashQuality);
        $otherHash = $this->hashFile($otherFilePath, $imageHash, $hashQuality);

        return $this->compareHashResults($fileHash, $otherHash);
    }

    public function compareHashResults(array $hashsA, array $hashsB): int
    {
        // The same sha1 beats everything
        if ($hashsA['sha1'] === $hashsB['sha1']) {
            return 0;
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
            if (array_sum($compare) === 0) {
                return 0;
            }

            return (array_sum($compare) / count($compare));
        }

        // Total different as fallback
        return 100;
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

    private function fetchHashQualityByImage(string $filePath, int $hashQuality)
    {
        $this->ensureHashQuality($hashQuality);

        if ($hashQuality > 0) {
            return $hashQuality;
        }

        // -1 is AUTO

        $result = getimagesize($filePath);

        if ($result[0] == 0 || $result[1] == 0) {
            throw new \Exception("The dimensions of image file: {$filePath} could not be fetched.");
        }

        $pixelcount = $result[0] * $result[1];

        switch (true) {
            case $pixelcount <= 100000: return self::HASH_IMAGE_RESIZE_DEFAULT;
            case $pixelcount >= 100001 && $pixelcount <= 2000000: return self::HASH_IMAGE_RESIZE_DOUBLE;
            case $pixelcount >= 2000001 && $pixelcount <= 10000000: return self::HASH_IMAGE_RESIZE_QUADRUPLE;
            case $pixelcount >= 10000001 && $pixelcount <= 16000000: return self::HASH_IMAGE_RESIZE_OCTUPLE;
            case $pixelcount >= 16000001 && $pixelcount <= 32000000: return self::HASH_IMAGE_RESIZE_SEXDECUPLE;
            default: return self::HASH_IMAGE_RESIZE_DUOTRIGUPLE;
        }
    }

    private function ensureHashQuality(int $hashQuality)
    {
        if ($hashQuality === 0) {
            throw new \Exception("A image hash quality of 0 is not supported (and useless).");
        }
    }
}