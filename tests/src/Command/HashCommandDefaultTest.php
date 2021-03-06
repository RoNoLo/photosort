<?php

namespace App\Tests\Command;

use App\Command\HashCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HashCommandTest extends BaseTestCase
{
    private $sourcePath;

    private $outputFile;

    public function setUp()
    {
        $this->fixtureFile = 'hash-default.yaml';

        parent::setUp();

        $this->sourcePath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'source';
        $this->outputFile = $this->sourcePath . DIRECTORY_SEPARATOR . HashCommand::HASH_OUTPUT_FILENAME;
    }

    public function testHashingRecursive()
    {
        $this->app->add(new HashCommand(new HashService()));

        $command = $this->app->find('app:hash');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
        ]);

        $this->assertFileExists($this->outputFile);

        $array = $this->readDataFromJsonFile($this->outputFile);

        $this->assertEquals(20, count($array));

        foreach ($array as $filepath => $hashs) {
            $this->assertArrayHasKey('sha1', $hashs);
            if (extension_loaded('imagick')) {
                $this->assertArrayHasKey('signature', $hashs);
            }
        }
    }
}