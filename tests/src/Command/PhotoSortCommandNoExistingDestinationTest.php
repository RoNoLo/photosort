<?php

namespace App\Tests\Command;

use App\Command\SortCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use App\Tests\Service\DirectoryStructureCheckerService;
use Symfony\Component\Console\Tester\CommandTester;

class PhotoSortCommandNoExistingDestinationTest extends BaseTestCase
{
    var $sourcePath;

    var $destinationPath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/photo-sort-no-existing-files.yaml';

        parent::setUp();

        $destinationPath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'destination';
        if (!$this->filesystem->exists($destinationPath)) {
            $this->filesystem->mkdir($destinationPath);
        }

        $this->sourcePath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'source');
        $this->destinationPath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'destination');
    }

    public function testOptionsAreDefaultAndNoExistingStructure()
    {
        $this->app->add(new SortCommand($this->filesystem, new HashService()));

        $command = $this->app->find('app:photo-sort');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            'destination-path' => $this->destinationPath,
        ]);

        $structureTester = new DirectoryStructureCheckerService($this->filesystem);

        $result = $structureTester->check($this->fixtureFile, $this->destinationPath);

        $this->assertTrue($result);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . SortCommand::PHOTOSORT_OUTPUT_FILENAME);

        $json = file_get_contents($this->sourcePath . DIRECTORY_SEPARATOR . SortCommand::PHOTOSORT_OUTPUT_FILENAME);
        $log = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertArrayHasKey('source', $log);
        $this->assertArrayHasKey('destination', $log);
        $this->assertArrayHasKey('stats', $log);
        $this->assertArrayHasKey('created', $log);
        $this->assertArrayHasKey('log', $log);

        $this->assertEquals(18, count($log['log']));
        $this->assertEquals(18, $log['stats']['totals']);
        $this->assertEquals(16, $log['stats']['copied']);
        $this->assertEquals(2, $log['stats']['identical']);
        $this->assertEquals(0, $log['stats']['errors']);
        $this->assertEquals(0, $log['stats']['skipped']);
    }
}