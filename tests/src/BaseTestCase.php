<?php

namespace App\Tests;

use App\Command\DirectoryStructureCheckCommand;
use App\Command\FixtureCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
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

        $this->app = new Application();
        $this->app->add(new FixtureCommand());
        $this->app->add(new DirectoryStructureCheckCommand());

        if (!is_null($this->fixtureFile) && $this->filesystem->exists($this->fixtureFile)) {
            $command = $this->app->find('tests:fixture');
            $commandTester = new CommandTester($command);
            $commandTester->execute([
                'command' => $command->getName(),
                'fixture-file' => $this->fixtureFile,
                'resource-path' => realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources'),
                'destination-path' => realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp'),
            ]);
        }
    }
}