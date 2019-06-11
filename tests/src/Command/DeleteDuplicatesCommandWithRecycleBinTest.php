<?php

namespace App\Tests\Command;

use App\Command\DeleteCommand;
use App\Command\DupsCommand;
use App\Command\HashCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteDuplicatesCommandWithRecycleBinTest extends BaseTestCase
{
    private $sourcePath;

    private $outputPath;

    public function setUp()
    {
        $this->fixtureFile = 'delete-duplicates.yaml';

        parent::setUp();

        $this->sourcePath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'source');
        $this->outputPath = realpath($this->testDestinationPath);
    }

    public function testDeleteDuplicates()
    {
        $sourceFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . HashCommand::HASH_OUTPUT_FILENAME;
        $duplicatesFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . DupsCommand::FINDDUPLICATES_OUTPUT_FILENAME;

        $this->app->add(new HashCommand(new HashService()));
        $this->app->add(new DupsCommand(new HashService()));
        $this->app->add(new DeleteCommand());

        $command = $this->app->find('app:hash');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-file' => $sourceFile,
        ]);

        $command = $this->app->find('app:dups');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'sources' => [$sourceFile],
        ]);

        $command = $this->app->find('app:delete');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $duplicatesFile,
            '--keep-first' => true,
        ]);

        $foo = 1;
    }
}