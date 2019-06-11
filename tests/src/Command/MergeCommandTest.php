<?php

namespace App\Tests\Command;

use App\Command\HashCommand;
use App\Command\MergeCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MergeCommandTest extends BaseTestCase
{
    /** @var string */
    private $sourcePath;

    /** @var string */
    private $outputFile;

    public function setUp()
    {
        $this->fixtureFile = 'hash-merge.yaml';

        parent::setUp();

        $this->sourcePath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'source';
        $this->outputFile = $this->testDestinationPath . DIRECTORY_SEPARATOR . MergeCommand::HASHMERGE_OUTPUT_MERGE_FILENAME;
    }

    public function testHasingImagesRecursive()
    {
        $this->app->add(new HashCommand(new HashService()));
        $this->app->add(new MergeCommand());

        $command = $this->app->find('app:hash');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath . DIRECTORY_SEPARATOR . 'bernd',
            '--output-file' => $this->sourcePath . DIRECTORY_SEPARATOR . 'bernd.json', // 4
        ]);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath . DIRECTORY_SEPARATOR . 'horst',
            '--output-file' => $this->sourcePath . DIRECTORY_SEPARATOR . 'horst.json', // 8
        ]);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath . DIRECTORY_SEPARATOR . 'peter',
            '--output-file' => $this->sourcePath . DIRECTORY_SEPARATOR . 'peter.json', // 6
        ]);

        $command = $this->app->find('app:merge');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'sources' => [
                $this->sourcePath . DIRECTORY_SEPARATOR . 'bernd.json',
                $this->sourcePath . DIRECTORY_SEPARATOR . 'horst.json',
                $this->sourcePath . DIRECTORY_SEPARATOR . 'peter.json'
            ],
            '--output-file' => $this->outputFile,
        ]);

        $mergedFilePath = $this->outputFile;

        $this->assertFileExists($mergedFilePath);

        $array = $this->readDataFromJsonFile($mergedFilePath);

        $this->assertEquals(20, count($array));

        foreach ($array as $filepath => $hashs) {
            $this->assertArrayHasKey('sha1', $hashs);
            if (extension_loaded('imagick')) {
                $this->assertArrayHasKey('signature', $hashs);
            }
        }
    }
}