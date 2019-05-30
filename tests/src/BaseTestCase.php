<?php

namespace App\Tests;

use App\Service\FixtureService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Exception\IOException;
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

    public function setUp()
    {
        $this->filesystem = new Filesystem();

        $this->app = new Application();

        if (!is_null($this->fixtureFile) && $this->filesystem->exists($this->fixtureFile)) {
            $fixtureService = new FixtureService($this->filesystem);

            $randomPart = substr(sha1(random_bytes(10)), 0, 10);

            $resourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';
            $testDestinationPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $randomPart;

            $this->filesystem->mkdir($testDestinationPath);

            $fixtureService->create($this->fixtureFile, $resourcePath, $testDestinationPath);

            $this->testDestinationPath = realpath($testDestinationPath);
        }
    }

    protected function normalizePath($path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    protected function tearDown()
    {
        if ($this->filesystem->exists($this->testDestinationPath)) {
            $this->filesystem->remove($this->testDestinationPath);
        }
    }
}