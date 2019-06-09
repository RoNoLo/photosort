<?php

namespace App\Tests\Command;

use App\Command\DuplicatesFindCommand;
use App\Command\HashCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DuplicatesFindCommandFileOnlyTest extends BaseTestCase
{
    private $sourcePath;

    public function setUp()
    {
        $this->fixtureFile = 'find-duplicates.yaml';

        parent::setUp();

        $this->sourcePath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'source');
    }

    public function testFindDuplicates()
    {
        $this->app->add(new HashCommand(new HashService()));
        $command = $this->app->find('app:hash');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-file' => $this->sourcePath . DIRECTORY_SEPARATOR . HashCommand::HASH_OUTPUT_FILENAME,
       ]);

        $this->app->add(new DuplicatesFindCommand(new HashService()));
        $command = $this->app->find('app:dups');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'sources' => [$this->sourcePath . DIRECTORY_SEPARATOR . HashCommand::HASH_OUTPUT_FILENAME],
        ]);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . DuplicatesFindCommand::FINDDUPLICATES_OUTPUT_FILENAME);

        $result = $this->readDataFromJsonFile(
            $this->sourcePath . DIRECTORY_SEPARATOR . DuplicatesFindCommand::FINDDUPLICATES_OUTPUT_FILENAME
        );

        $this->assertCount(6, $result);
    }
}