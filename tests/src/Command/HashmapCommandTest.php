<?php

namespace RoNoLo\PhotoSort\Command;

use PHPUnit\Framework\TestCase;
use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class HashmapCommandTest extends TestCase
{
    var $testPath;

    /** @var Filesystem */
    var $fs;

    public function setUp()
    {
        $this->testPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . sha1(uniqid(microtime()));
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->testPath);
    }

    public function testHasingImagesRecursive()
    {
        $hashs = $this->_setupHashingImagesRecursive();

        $app = new Application();
        $app->add(new HashmapCommand());

        $command = $app->find('ps:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => $this->testPath,
            '--recursive'
        ]);

        $this->assertFileExists($this->testPath . DIRECTORY_SEPARATOR . 'photosort_path2hash.json');
        $this->assertFileExists($this->testPath . DIRECTORY_SEPARATOR . 'photosort_hash2path.json');

        $json = file_get_contents($this->testPath . DIRECTORY_SEPARATOR . 'photosort_path2hash.json');
        $array = json_decode($json, JSON_PRETTY_PRINT);

        foreach ($hashs as $path => $hash) {
            $this->assertArrayHasKey($path, $array);
        }
    }

    public function testHasingImagesNotRecursive()
    {
        $hashs = $this->_setupHashingImagesNotRecursive();

        $app = new Application();
        $app->add(new HashmapCommand());

        $command = $app->find('hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => $this->testPath
        ]);

        $this->assertFileExists($this->testPath . DIRECTORY_SEPARATOR . 'photosort_path2hash.json');
        $this->assertFileExists($this->testPath . DIRECTORY_SEPARATOR . 'photosort_hash2path.json');

        $json = file_get_contents($this->testPath . DIRECTORY_SEPARATOR . 'photosort_path2hash.json');
        $array = json_decode($json, JSON_PRETTY_PRINT);

        foreach ($hashs as $path => $hash) {
            $this->assertArrayHasKey($path, $array);
        }
    }

    public function tearDown()
    {
        if ($this->fs->exists($this->testPath)) {
            $this->fs->remove($this->testPath);
        }
    }

    private function _setupHashingImagesRecursive()
    {
        $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_001.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'image_001.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_002.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'image_002.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_003.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'image_003.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_013.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'image_013.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_014.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'image_014.jpg');

        $this->fs->mkdir($this->testPath . DIRECTORY_SEPARATOR . 'peter');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_004.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_005.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_006.jpg');

        $this->fs->mkdir($this->testPath . DIRECTORY_SEPARATOR . 'bernd');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_004.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_005.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_006.jpg');

        $this->fs->mkdir($this->testPath . DIRECTORY_SEPARATOR . 'horst');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_007.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_008.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_009.jpg');
        $this->fs->mkdir($this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_007.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_007.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_008.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_008.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_010.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_010.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_011.jpg', $this->testPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_011.jpg');

        $filesIterator = $this->fs->files($this->testPath, true);

        $hashs = [];

        /** @var \SplFileInfo $file */
        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $hash = $this->fs->hash($file);
            $hashs[$file->getRealPath()] = $hash;
        }

        return $hashs;
    }

    private function _setupHashingImagesNotRecursive()
    {
        $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $this->fs->mirror($sourcePath, $this->testPath);

        $filesIterator = $this->fs->files($this->testPath);

        $hashs = [];

        /** @var \SplFileInfo $file */
        foreach ($filesIterator as $file) {
            $hash = $this->fs->hash($file);
            $hashs[$file->getRealPath()] = $hash;
        }

        return $hashs;
    }
}