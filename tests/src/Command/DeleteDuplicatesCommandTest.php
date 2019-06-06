<?php

namespace App\Tests\Command;

use App\Command\DuplicatesDeleteCommand;
use App\Command\DuplicatesFindCommand;
use App\Command\HashCommand;
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
        $sourceFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . HashCommand::HASHMAP_OUTPUT_FILENAME;
        $duplicatesFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . DuplicatesFindCommand::FINDDUPLICATES_OUTPUT_FILENAME;

        $this->app->add(new HashCommand(new HashService()));
        $this->app->add(new DuplicatesFindCommand(new HashService()));
        $this->app->add(new DuplicatesDeleteCommand());

        $command = $this->app->find('app:hash');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->outputPath,
            '--image-hashs' => true
        ]);

        $command = $this->app->find('app:dups');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $sourceFile,
        ]);

        $command = $this->app->find('app:delete');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $duplicatesFile,
            '--always-keep-first-in-list' => true,
        ]);

        $foo = 1;
    }
}