<?php

namespace App\Tests\Command;

use App\Command\DuplicatesFindCommand;
use App\Command\HashCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DuplicatesFindCommandTest extends BaseTestCase
{
    public function testFindDuplicates()
    {
        $sourceFile = $this->resourcesPath . DIRECTORY_SEPARATOR . HashCommand::HASH_OUTPUT_FILENAME;
        $duplicatesFile = $this->resourcesPath . DIRECTORY_SEPARATOR . DuplicatesFindCommand::FINDDUPLICATES_OUTPUT_FILENAME;

        $this->app->add(new DuplicatesFindCommand(new HashService()));
        $command = $this->app->find('app:dups');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'sources' => [$sourceFile],
        ]);

        $this->assertFileExists($duplicatesFile);

        $result = $this->readDataFromJsonFile($duplicatesFile);

        $this->assertCount(234, $result);
    }
}