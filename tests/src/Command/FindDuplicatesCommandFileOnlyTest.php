<?php

namespace RoNoLo\PhotoSort\Command;

use App\Command\FindDuplicatesCommand;
use App\Command\HashMapCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommandFileOnlyTest extends BaseTestCase
{
    private $sourcePath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/find-duplicates.yaml';

        parent::setUp();

        $this->sourcePath = realpath($this->testDestinationPath . DIRECTORY_SEPARATOR . 'source');
    }

    public function testFindDuplicates()
    {
        $this->app->add(new HashMapCommand($this->filesystem, new HashService()));
        $command = $this->app->find('app:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->sourcePath,
            '--image-hashs' => true
        ]);

        $this->app->add(new FindDuplicatesCommand($this->filesystem, new HashService()));
        $command = $this->app->find('app:find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $this->sourcePath . DIRECTORY_SEPARATOR . HashMapCommand::HASHMAP_OUTPUT_FILENAME,
        ]);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');

        $json = file_get_contents($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');
        $result = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertCount(6, $result);
    }
}