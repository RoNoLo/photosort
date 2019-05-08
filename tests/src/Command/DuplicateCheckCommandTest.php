<?php

namespace RoNoLo\PhotoSort\Command;

use PHPUnit\Framework\TestCase;
use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DuplicateCheckCommandTest extends TestCase
{
    var $testSourcePath;

    var $testDestinationPath;

    /** @var Filesystem */
    var $fs;

    public function setUp()
    {
        $this->testSourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . sha1(uniqid(microtime()));
        $this->testDestinationPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . sha1(uniqid(microtime()));

        $this->fs = new Filesystem();
        $this->fs->mkdir($this->testSourcePath);
        $this->fs->mkdir($this->testDestinationPath);
    }

    public function testHasingImagesRecursive()
    {
        $hashs = $this->_setupHashingImagesRecursive();

        $app = new Application();
        $app->add(new DuplicateCheckCommand());
        $app->add(new HashmapCommand());

        $command = $app->find('find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => $this->testSourcePath,
            'destination' => $this->testDestinationPath,
        ]);

        $this->assertFileExists($this->testSourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');
    }

    public function tearDown()
    {
        if ($this->fs->exists($this->testSourcePath)) {
            $this->fs->remove($this->testSourcePath);
        }
        if ($this->fs->exists($this->testDestinationPath)) {
            $this->fs->remove($this->testDestinationPath);
        }
    }

    public function _setupHashingImagesRecursive()
    {
        $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $this->fs->mirror($sourcePath, $this->testSourcePath);

        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_001.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'image_001.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_002.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'image_002.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_003.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'image_003.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_013.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'image_013.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_014.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'image_014.jpg');

        $this->fs->mkdir($this->testDestinationPath . DIRECTORY_SEPARATOR . 'peter');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_004.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_005.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'peter' . DIRECTORY_SEPARATOR . 'image_006.jpg');

        $this->fs->mkdir($this->testDestinationPath . DIRECTORY_SEPARATOR . 'bernd');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_004.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_005.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'bernd' . DIRECTORY_SEPARATOR . 'image_006.jpg');

        $this->fs->mkdir($this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_004.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_007.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_005.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_008.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_006.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'image_009.jpg');
        $this->fs->mkdir($this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_007.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_007.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_008.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_008.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_010.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_010.jpg');
        $this->fs->copy($sourcePath . DIRECTORY_SEPARATOR . 'image_011.jpg', $this->testDestinationPath . DIRECTORY_SEPARATOR . 'horst' . DIRECTORY_SEPARATOR . 'tommy' . DIRECTORY_SEPARATOR . 'image_011.jpg');

        $filesIterator = $this->fs->files($this->testDestinationPath, true);

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
}