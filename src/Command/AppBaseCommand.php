<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class AppBaseCommand extends Command
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
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