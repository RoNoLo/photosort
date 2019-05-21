<?php

namespace App\Tests;

use App\Service\HashService;
use Symfony\Component\Finder\Finder;

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
        $finder = Finder::create()->files()->name(['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'])->in($this->sourcePath);

        $hasher = new HashService();

        $hashs = $hasher->hashFiles($finder, true);

        $this->assertEquals(18, count($hashs));

        foreach ($hashs as $filePath => $list) {
            $this->assertArrayHasKey('sha1', $list);
            $this->assertArrayHasKey('difference', $list);
            $this->assertArrayHasKey('average', $list);
            $this->assertArrayHasKey('perceptual', $list);

            $this->assertNotEmpty($list['sha1']);
            $this->assertNotEmpty($list['difference']);
            $this->assertNotEmpty($list['average']);
            $this->assertNotEmpty($list['perceptual']);
        }
    }

    public function testCreatingHashOfFile()
    {
        $filePath = $this->sourcePath . DIRECTORY_SEPARATOR . 'image_001.jpg';

        $hasher = new HashService();

        $hashs = $hasher->hashFile($filePath, true);

        $this->assertArrayHasKey('sha1', $hashs);
        $this->assertArrayHasKey('difference', $hashs);
        $this->assertArrayHasKey('average', $hashs);
        $this->assertArrayHasKey('perceptual', $hashs);

        $this->assertNotEmpty($hashs['sha1']);
        $this->assertNotEmpty($hashs['difference']);
        $this->assertNotEmpty($hashs['average']);
        $this->assertNotEmpty($hashs['perceptual']);
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