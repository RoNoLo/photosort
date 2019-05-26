<?php

namespace RoNoLo\PhotoSort\Command;

use App\Command\HashMapCommand;
use App\Command\PhotoSortCommand;
use App\Service\DirectoryStructureCheckerService;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class PhotoSortCommandDuplicateButNotIdenticalTest extends BaseTestCase
{
    var $sourcePath;

    var $destinationPath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/photo-sort-duplicate-but-not-identical.yaml';

        parent::setUp();

        $tmpPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp';
        $destinationPath = $tmpPath . DIRECTORY_SEPARATOR . 'destination';
        if (!$this->filesystem->exists($destinationPath)) {
            $this->filesystem->mkdir($destinationPath);
        }

        $this->sourcePath = realpath($tmpPath . DIRECTORY_SEPARATOR . 'source');
        $this->destinationPath = realpath($tmpPath . DIRECTORY_SEPARATOR . 'destination');
    }

    public function testOptionsAreDefaultAndNoExistingStructure()
    {
        $this->app->add(new PhotoSortCommand(new Filesystem(), new HashService()));

        $command = $this->app->find('photosort:photo-sort');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            'destination-path' => $this->destinationPath,
        ]);

        $structureTester = new DirectoryStructureCheckerService($this->filesystem);

        $result = $structureTester->check($this->fixtureFile, $this->destinationPath);

        $this->assertTrue($result);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_log.json');

        $json = file_get_contents($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_log.json');
        $log = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertArrayHasKey('source', $log);
        $this->assertArrayHasKey('destination', $log);
        $this->assertArrayHasKey('stats', $log);
        $this->assertArrayHasKey('created', $log);
        $this->assertArrayHasKey('log', $log);

        $this->assertEquals(3, count($log['log']));
        $this->assertEquals(3, $log['stats']['totals']);
        $this->assertEquals(2, $log['stats']['copied']);
        $this->assertEquals(1, $log['stats']['identical']);
        $this->assertEquals(0, $log['stats']['errors']);
        $this->assertEquals(0, $log['stats']['skipped']);
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