<?php

namespace App\Tests\Service;

use App\Command\HashCommand;
use App\Service\HashService;
use App\Tests\BaseTestCase;
use Symfony\Component\Finder\Finder;

class HashServiceTest extends BaseTestCase
{
    private $sourcePath;

    private $outputPath;

    public function setUp()
    {
        $this->fixtureFile = 'hash-map.yaml';

        parent::setUp();

        $this->sourcePath = $this->testDestinationPath . DIRECTORY_SEPARATOR . 'source';
        $this->outputPath = $this->testDestinationPath;
    }

    public function testCreatingHashsOfPath()
    {
        $finder = Finder::create()
            ->files()
            ->name(HashCommand::HASH_IMAGES)
            ->in($this->sourcePath);

        $hasher = new HashService();

        $hashs = $hasher->hashFiles($finder, true);

        $this->assertEquals(20, count($hashs));

        foreach ($hashs as $filePath => $list) {
            $this->assertArrayHasKey('sha1', $list);
            $this->assertNotEmpty($list['sha1']);

            if (extension_loaded('imagick')) {
                $this->assertArrayHasKey('signature', $list);
                $this->assertNotEmpty($list['signature']);
            }
        }
    }

    public function testCreatingHashOfFile()
    {
        $filePath = $this->sourcePath . DIRECTORY_SEPARATOR . 'image_001.jpg';

        $hasher = new HashService();

        $hashs = $hasher->hashFile($filePath, true);

        $this->assertArrayHasKey('sha1', $hashs);
        $this->assertNotEmpty($hashs['sha1']);

        if (extension_loaded('imagick')) {
            $this->assertArrayHasKey('signature', $hashs);
            $this->assertNotEmpty($hashs['signature']);
        }
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

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
        $this->assertFalse($result4);
        $this->assertFalse($result5);
        $this->assertTrue($result6);
        $this->assertFalse($result7);
        $this->assertFalse($result8);
        $this->assertFalse($result9);
        $this->assertFalse($result10);
        $this->assertFalse($result11);
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

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
        $this->assertFalse($result4);
        $this->assertFalse($result5);
        $this->assertTrue($result6);
        $this->assertFalse($result7);
        $this->assertFalse($result8);
        $this->assertFalse($result9);
        $this->assertFalse($result10);
        $this->assertFalse($result10);
        $this->assertFalse($result11);
    }
}