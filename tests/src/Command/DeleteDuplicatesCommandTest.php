<?php

namespace App\Tests\Command;

use App\Command\DeleteDuplicatesCommand;
use App\Command\HashMapCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

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
        $this->app->add(new DeleteDuplicatesCommand());

        $command = $this->app->find('app:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->outputPath,
            '--image-hashs' => true
        ]);

        $this->assertFileExists($this->outputPath . DIRECTORY_SEPARATOR . HashMapCommand::HASHMAP_OUTPUT_FILENAME);

        $json = file_get_contents($this->outputPath . DIRECTORY_SEPARATOR . HashMapCommand::HASHMAP_OUTPUT_FILENAME);
        $array = json_decode($json, JSON_PRETTY_PRINT);

        $this->assertEquals(20, count($array));

        foreach ($array as $filepath => $hashs) {
            $this->assertArrayHasKey('sha1', $hashs);
            if (extension_loaded('imagick')) {
                $this->assertArrayHasKey('signature', $hashs);
            }
        }
    }
}