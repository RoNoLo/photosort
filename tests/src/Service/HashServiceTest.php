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

    public function testCheckForSimilarPictures()
    {
        $resourcesPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $filePath1 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_002.jpg';
        $filePath2 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_003.jpg';
        $filePath3 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_004.jpg';
        $filePath4 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_010.jpg';
        $filePath5 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_011.jpg';
        $filePath6 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_003-compressor.jpg';
        $filePath7 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_007.jpg';
        $filePath8 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_007-compressor.jpg';
        $filePath9 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_011-compressor.jpg';

        $hasher = new HashService();

        $result1 = $hasher->compareFile($filePath1, $filePath1, true);
        $result2 = $hasher->compareFile($filePath1, $filePath2, true);
        $result3 = $hasher->compareFile($filePath1, $filePath3, true);
        $result4 = $hasher->compareFile($filePath1, $filePath4, true);
        $result5 = $hasher->compareFile($filePath1, $filePath5, true);
        $result6 = $hasher->compareFile($filePath4, $filePath4, true);
        $result7 = $hasher->compareFile($filePath4, $filePath5, true);
        $result8 = $hasher->compareFile($filePath6, $filePath7, true);
        $result9 = $hasher->compareFile($filePath2, $filePath6, true);
        $result10 = $hasher->compareFile($filePath7, $filePath8, true);
        $result11 = $hasher->compareFile($filePath5, $filePath9, true);

        $this->assertEquals(0, $result1);
        $this->assertTrue($result2 > 0);
        $this->assertTrue($result3 > 0);
        $this->assertTrue($result4 > 0);
        $this->assertTrue($result5 > 0);
        $this->assertEquals(0, $result6);
        $this->assertTrue($result7 > 0);
        $this->assertTrue($result8 > 0);
        $this->assertEquals(1, $result9);
        $this->assertEquals(0, $result10);
        $this->assertEquals(0, $result11);
    }

    public function testCheckForSimilarPicturesMorePixels()
    {
        $resourcesPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

        $filePath1 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_002.jpg';
        $filePath2 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_003.jpg';
        $filePath3 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_004.jpg';
        $filePath4 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_010.jpg';
        $filePath5 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_011.jpg';
        $filePath6 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_003-compressor.jpg';
        $filePath7 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_007.jpg';
        $filePath8 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_007-compressor.jpg';
        $filePath9 = $resourcesPath . DIRECTORY_SEPARATOR . 'image_011-compressor.jpg';

        $hasher = new HashService();

        $result1 = $hasher->compareFile($filePath1, $filePath1, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result2 = $hasher->compareFile($filePath1, $filePath2, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result3 = $hasher->compareFile($filePath1, $filePath3, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result4 = $hasher->compareFile($filePath1, $filePath4, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result5 = $hasher->compareFile($filePath1, $filePath5, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result6 = $hasher->compareFile($filePath4, $filePath4, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result7 = $hasher->compareFile($filePath4, $filePath5, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result8 = $hasher->compareFile($filePath6, $filePath7, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result9 = $hasher->compareFile($filePath2, $filePath6, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result10 = $hasher->compareFile($filePath7, $filePath8, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);
        $result11 = $hasher->compareFile($filePath5, $filePath9, true, HashService::HASH_IMAGE_RESIZE_DOUBLE);

        $this->assertEquals(0, $result1);
        $this->assertTrue($result2 > 0);
        $this->assertTrue($result3 > 0);
        $this->assertTrue($result4 > 0);
        $this->assertTrue($result5 > 0);
        $this->assertEquals(0, $result6);
        $this->assertTrue($result7 > 0);
        $this->assertTrue($result8 > 0);
        $this->assertTrue($result9 > 0);
        $this->assertTrue($result10 > 0);
        $this->assertTrue($result11 > 0);
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