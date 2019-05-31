<?php

namespace RoNoLo\PhotoSort\Command;

use App\Command\FindDuplicatesCommand;
use App\Command\HashMapCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommandTest extends BaseTestCase
{
    private $resourcesPath;

    public function setUp()
    {
        parent::setUp();

        $this->resourcesPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources');
    }

    public function testFindDuplicates()
    {
        $sourceFile = $this->resourcesPath . DIRECTORY_SEPARATOR . HashMapCommand::HASHMAP_OUTPUT_FILENAME;
        $duplicatesFile = $this->resourcesPath . DIRECTORY_SEPARATOR . FindDuplicatesCommand::FINDDUPLICATES_OUTPUT_FILENAME;

        $this->app->add(new FindDuplicatesCommand($this->filesystem, new HashService()));
        $command = $this->app->find('app:find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $sourceFile,
        ]);

        $this->assertFileExists($duplicatesFile);

        $json = file_get_contents($duplicatesFile);
        $result = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertCount(234, $result);
    }
}