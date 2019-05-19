<?php

namespace App\Tests\Command;

use App\Command\HashMapCommand;
use App\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HashMapCommandTest extends BaseTestCase
{
    private $sourcePath;

    private $outputPath;

    public function setUp()
    {
        $this->fixtureFile = __DIR__ . '/../../fixtures/hash-map.yaml';

        parent::setUp();

        $this->sourcePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'source');
        $this->outputPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp');
    }

    public function testHasingImagesRecursive()
    {
        $this->app->add(new HashMapCommand());

        $command = $this->app->find('photosort:hash-map');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'source-path' => $this->sourcePath,
            '--output-path' => $this->outputPath,
        ]);

        $this->assertFileExists($this->outputPath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json');

        $json = file_get_contents($this->outputPath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json');
        $array = json_decode($json, JSON_PRETTY_PRINT);

        $this->assertEquals(12, count($array['hashs']));
        $this->assertEquals(18, count($array['paths']));
        $this->assertEquals(null, $array['empty_hash']);
        $this->assertEquals([], $array['errors']);
    }

    protected function tearDown()
    {
        if ($this->filesystem->exists($this->sourcePath)) {
            $this->filesystem->remove($this->sourcePath);
        }

        if ($this->filesystem->exists($this->outputPath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json')) {
            $this->filesystem->remove($this->outputPath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json');
        }
    }
}