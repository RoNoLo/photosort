<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class AppBaseCommand extends Command
{
    protected Filesystem $filesystem;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected Stopwatch $stopwatch;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->stopwatch = new Stopwatch();

        parent::__construct();

        $this->stopwatch->start(static::getName());
    }

    public function __destruct()
    {
        if ($this->output && $this->output->isVerbose()) {
            $this->output->writeln((string) $this->stopwatch->stop(static::getName()));
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function persistInput(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function readJsonFilesAndMerge($filePaths): array
    {
        $data = [];

        foreach ($filePaths as $source) {
            $tmp = $this->readJsonFile($source);
            $data = array_merge($data, $tmp);
        }

        return $data;
    }

    protected function readJsonFile($filePath)
    {
        $data = json_decode(file_get_contents(realpath($filePath)), JSON_PRETTY_PRINT);

        return $data;
    }

    protected function writeJsonFile($filepath, $data)
    {
        $this->filesystem->dumpFile($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }
}