<?php

namespace RoNoLo\PhotoSort\Command;

use App\Command\HashMapCommand;
use App\Command\PhotoSortCommand;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PhotoSortCommandNoExistingDestinationTest extends BaseTestCase
{
    var $sourcePath;

    var $destinationPath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/photo-sort-no-existing-files.yaml';

        parent::setUp();

        $tmpPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp';
        $this->filesystem->mkdir($tmpPath . DIRECTORY_SEPARATOR . 'destination');

        $this->sourcePath = realpath($tmpPath . DIRECTORY_SEPARATOR . 'source');
        $this->destinationPath = realpath($tmpPath . DIRECTORY_SEPARATOR . 'destination');
    }

    public function testOptionsAreDefaultAndNoExistingStructure()
    {
        $this->app->add(new PhotoSortCommand());
        $this->app->add(new HashMapCommand());

        $command = $this->app->find('photosort:photo-sort');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            'destination-path' => $this->destinationPath,
        ]);

        $command = $this->app->find('tests:directory-structure-check');
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([
            'command' => $command->getName(),
            'root-path' => $this->destinationPath,
            'fixture-file' => $this->fixtureFile,
        ]);

        $this->assertEquals(0, $result);
    }

    protected function tearDown()
    {
        if ($this->filesystem->exists($this->sourcePath)) {
            $this->filesystem->remove($this->sourcePath);
        }

        if ($this->filesystem->exists($this->destinationPath)) {
            $this->filesystem->remove($this->destinationPath);
        }
    }
}