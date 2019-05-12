<?php

namespace RoNoLo\PhotoSort\Command;

use PHPUnit\Framework\TestCase;
use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FindDuplicatesCommandTest extends TestCase
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
        $app->add(new FindDuplicatesCommand());
        $app->add(new HashMapCommand());

        $command = $app->find('find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => $this->testSourcePath,
            'destination' => $this->testDestinationPath,
        ]);

        $this->assertFileExists($this->testSourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');

        $json = file_get_contents($this->testSourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');
        $array = json_decode($json, JSON_PRETTY_PRINT);

        $this->assertEquals(2, count($array['9fa5a76bf8651d38f7a6a14050bfdda559777f5e']));
        $this->assertEquals(2, count($array['0e34f2bbf52b4726c34d1cb883fc2aced67bc9f6']));
        $this->assertEquals(2, count($array['971ae892b512354d55298491132baec4e5848b11']));
        $this->assertEquals(4, count($array['4dd8c909f792f4dc7438a618b7d4237a07a33917']));
        $this->assertEquals(4, count($array['02e4e7c7f6a88fb4feafa146044cae625e7cae4c']));
        $this->assertEquals(4, count($array['22de714263a4003615789d4ec22019f4d8e4c4a9']));
        $this->assertEquals(2, count($array['5c91ca14a865d1c639c074a2478c702b8c3e65f5']));
        $this->assertEquals(2, count($array['2b3df5bdf4f085049dd3eb51ea1b158344b87235']));
        $this->assertEquals(2, count($array['c180847d4ddbfd15e928d7a5f5d1751e210fa40e']));
        $this->assertEquals(2, count($array['906d1b7dd147799a58b2044f1a3e211af3f4f7dc']));
        $this->assertEquals(2, count($array['a7acf3c92eafd5b43d5c2de3d22592c96e7d4386']));
        $this->assertEquals(2, count($array['d2c3ee8152e607cee61ac6f84b4ec15fcb02335b']));
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