<?php

namespace App\Tests;

use App\Service\FixtureService;
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

    public function setUp()
    {
        $this->filesystem = new Filesystem();
        $fixtureService = new FixtureService($this->filesystem);

        $this->app = new Application();

        if (!is_null($this->fixtureFile) && $this->filesystem->exists($this->fixtureFile)) {
            $resourcePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';
            $destinationPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp';

            $fixtureService->create($this->fixtureFile, $resourcePath, $destinationPath);
        }
    }

    protected function normalizePath($path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}