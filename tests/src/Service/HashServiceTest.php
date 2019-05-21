<?php

namespace App\Tests;

class HashServiceTest extends BaseTestCase
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

    public function testCreatingHashsOfPath()
    {

    }

    public function testCreatingHashOfFile()
    {

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