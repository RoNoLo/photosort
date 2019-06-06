<?php

namespace App\Tests;

use App\Tests\Service\FixtureService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;

class BaseTestCase extends TestCase
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var Application */
    protected $app;

    /** @var string */
    protected $fixtureFile;

    /** @var string */
    protected $testDestinationPath;

    /** @var string */
    protected $resourcesPath;

    /** @var string */
    protected $fixturePath =  __DIR__ . DIRECTORY_SEPARATOR .
        '..' . DIRECTORY_SEPARATOR .
        'fixtures'
    ;

    public function setUp()
    {
        $this->filesystem = new Filesystem();

        $this->app = new Application();

        $this->fixturePath = realpath($this->fixturePath);

        $randomPart = substr(sha1(\random_bytes(10)), 0, 10);
        $this->resourcesPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';
        $testDestinationPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $randomPart;

        $this->filesystem->mkdir($testDestinationPath);
        $this->testDestinationPath = realpath($testDestinationPath);

        if (!is_null($this->fixtureFile) && $this->filesystem->exists($this->fixturePath . DIRECTORY_SEPARATOR . $this->fixtureFile)) {
            $fixtureService = new FixtureService($this->filesystem);
            $fixtureService->create(
                $this->fixturePath . DIRECTORY_SEPARATOR . $this->fixtureFile, $this->resourcesPath, $testDestinationPath
            );
        }
    }

    protected function normalizePath($path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    protected function readDataFromJsonFile($filepath)
    {
        $json = file_get_contents($filepath);
        $data = json_decode($json, JSON_PRETTY_PRINT);

        return $data;
    }

    protected function tearDown()
    {
        if ($this->filesystem->exists($this->testDestinationPath)) {
            $this->filesystem->remove($this->testDestinationPath);
        }
    }
}