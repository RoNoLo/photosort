<?php

namespace App\Tests\Command;

use App\Command\SortCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use App\Tests\Service\DirectoryStructureCheckerService;
use Symfony\Component\Console\Tester\CommandTester;

class PhotoSortCommandDuplicateButNotIdenticalNoRenameTest extends BaseTestCase
{
    var $sourcePath;

    var $destinationPath;

    public function setUp()
    {
        $this->fixtureFile = 'photo-sort-duplicate-but-not-identical-no-rename.yaml';

        parent::setUp();

        $sourcePath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'source';
        $destinationPath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'destination';
        if (!$this->filesystem->exists($destinationPath)) {
            $this->filesystem->mkdir($destinationPath);
        }

        $this->sourcePath = realpath($sourcePath);
        $this->destinationPath = realpath($destinationPath);
    }

    public function testOptionsAreDefaultAndNoExistingStructure()
    {
        $this->app->add(new SortCommand(new HashService()));

        $command = $this->app->find('app:sort');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            'destination-path' => $this->destinationPath,
            '--no-rename' => true
        ]);

        $structureTester = new DirectoryStructureCheckerService($this->filesystem);

        $result = $structureTester->check($this->fixturePath . DIRECTORY_SEPARATOR . $this->fixtureFile, $this->destinationPath);

        $this->assertTrue($result);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . SortCommand::PHOTOSORT_OUTPUT_FILENAME);

        $json = file_get_contents($this->sourcePath . DIRECTORY_SEPARATOR . SortCommand::PHOTOSORT_OUTPUT_FILENAME);
        $log = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertArrayHasKey('source', $log);
        $this->assertArrayHasKey('destination', $log);
        $this->assertArrayHasKey('stats', $log);
        $this->assertArrayHasKey('created', $log);
        $this->assertArrayHasKey('log', $log);

        $this->assertEquals(3, count($log['log']));
        $this->assertEquals(3, $log['stats']['totals']);
        $this->assertEquals(0, $log['stats']['copied']);
        $this->assertEquals(1, $log['stats']['identical']);
        $this->assertEquals(0, $log['stats']['errors']);
        $this->assertEquals(2, $log['stats']['skipped']);
    }
}