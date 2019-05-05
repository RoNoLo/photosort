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
        $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $this->fs->mirror($sourcePath, $this->testPath);

        $filesIterator = $this->fs->files($this->testPath);

        /** @var \SplFileInfo $file */
        foreach ($filesIterator as $file) {
            $hash = $this->fs->hash($file);
        }

        $app = new Application();
        $app->add(new HashmapCommand());

        $command = $app->find('ps:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source' => $this->testPath
        ]);
    }

    public function tearDown()
    {
        if ($this->fs->exists($this->testPath)) {
            // $this->fs->remove($this->testPath);
        }
    }
}