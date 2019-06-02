<?php

namespace App\Tests\Command;

use App\Command\DeleteDuplicatesCommand;
use App\Command\FindDuplicatesCommand;
use App\Command\HashMapCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteDuplicatesCommandTest extends BaseTestCase
{
    private $sourcePath;

    private $outputPath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/delete-duplicates.yaml';

        parent::setUp();

        $this->sourcePath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'source');
        $this->outputPath = realpath($this->testDestinationPath);
    }

    public function testDeleteDuplicates()
    {
        $sourceFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . HashMapCommand::HASHMAP_OUTPUT_FILENAME;
        $duplicatesFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . FindDuplicatesCommand::FINDDUPLICATES_OUTPUT_FILENAME;

        $this->app->add(new HashMapCommand($this->filesystem, new HashService()));
        $this->app->add(new FindDuplicatesCommand($this->filesystem, new HashService()));
        $this->app->add(new DeleteDuplicatesCommand($this->filesystem));

        $command = $this->app->find('app:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->outputPath,
            '--image-hashs' => true
        ]);

        $command = $this->app->find('app:find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $sourceFile,
        ]);

        $command = $this->app->find('app:delete-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $duplicatesFile,
            '--always-keep-first-in-list' => true,
        ]);

        $foo = 1;
    }
}