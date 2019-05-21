<?php

namespace App\Service;

use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use Jenssegers\ImageHash\Implementations\BlockHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use Symfony\Component\Finder\Finder;

class HashService
{
    public function hashFile(string $filePath, $imageHash = false)
    {
        $hashs = [];

        $hashs['sha1'] = sha1_file($filePath);

        if ($imageHash) {
            $differenceHasher = new ImageHash(new DifferenceHash());
            $averageHasher = new ImageHash(new AverageHash());
            // $blockHasher = new ImageHash(new BlockHash());
            $perceptualHasher = new ImageHash(new PerceptualHash());

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

    public function compareFile(string $filePath, string $otherFilePath)
    {
        $fileHash = $this->hashFile($filePath);
        $otherHash = $this->hashFile($otherFilePath);

        $compare = [
            'sha1' => $fileHash['sha1'] === $otherHash['sha1'],
            'difference' => Hash::fromHex($fileHash['difference'])->distance(Hash::fromHex($otherHash['difference'])),
            'average' => Hash::fromHex($fileHash['average'])->distance(Hash::fromHex($otherHash['average'])),
            'perceptual' => Hash::fromHex($fileHash['perceptual'])->distance(Hash::fromHex($otherHash['perceptual'])),
        ];

        return $compare;
    }
}