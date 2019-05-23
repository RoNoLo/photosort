<?php

namespace RoNoLo\PhotoSort\Command;

use App\Command\FindDuplicatesCommand;
use App\Command\HashMapCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use PHPUnit\Framework\Constraint\DirectoryExists;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommandTest extends BaseTestCase
{
    private $sourcePath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/find-duplicates.yaml';

        parent::setUp();

        $this->sourcePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'source');
    }

    public function testFindDuplicates()
    {
        $this->app->add(new HashMapCommand(new Filesystem(), new HashService()));
        $command = $this->app->find('photosort:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->sourcePath,
            '--image-hashs' => true
        ]);

        $this->app->add(new FindDuplicatesCommand(new Filesystem(), new HashService()));
        $command = $this->app->find('photosort:find-duplicates');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-file' => $this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json',
        ]);

        $this->assertFileExists($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');

        $json = file_get_contents($this->sourcePath . DIRECTORY_SEPARATOR . 'photosort_duplicates.json');
        $result = json_decode($json, JSON_OBJECT_AS_ARRAY);

        $this->assertCount(8, $result);
    }

    public function tearDown()
    {
        if ($this->filesystem->exists($this->sourcePath)) {
            $this->filesystem->remove($this->sourcePath);
        }
    }
}