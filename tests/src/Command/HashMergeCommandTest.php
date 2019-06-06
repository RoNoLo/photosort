<?php

namespace App\Tests\Command;

use App\Command\HashCommand;
use App\Command\HashMergeCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HashMergeCommandTest extends BaseTestCase
{
    /** @var string */
    private $resourcesPath;

    /** @var string */
    private $outputPath;

    public function setUp()
    {
        parent::setUp();

        $this->resourcesPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources');
        $this->outputPath = realpath($this->testDestinationPath);
    }

    public function testHasingImagesRecursive()
    {
        $this->app->add(new HashMergeCommand());

        $command = $this->app->find('app:merge');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'sources' => [
                $this->resourcesPath . DIRECTORY_SEPARATOR . 'hash1.json',
                $this->resourcesPath . DIRECTORY_SEPARATOR . 'hash2.json',
                $this->resourcesPath . DIRECTORY_SEPARATOR . 'hash3.json'
            ],
            '--output-path' => $this->outputPath,
        ]);

        $mergedFilePath = $this->outputPath . DIRECTORY_SEPARATOR . HashMergeCommand::HASHMERGE_OUTPUT_MERGE_FILENAME;
        $duplicatesHelperFilePath = $this->outputPath . DIRECTORY_SEPARATOR . HashMergeCommand::HASHMERGE_OUTPUT_DUPLICATES_HELPER_FILENAME;

        $this->assertFileExists($mergedFilePath);
        $this->assertFileExists($duplicatesHelperFilePath);

        $array = $this->readDataFromJsonFile($mergedFilePath);

        $this->assertEquals(15, count($array));

        foreach ($array as $filepath => $hashs) {
            $this->assertArrayHasKey('sha1', $hashs);
            if (extension_loaded('imagick')) {
                $this->assertArrayHasKey('signature', $hashs);
            }
        }
    }
}