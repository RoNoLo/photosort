<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HashmapCommand extends Command
{
    protected static $defaultName = 'hash-map';

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Recursive', true);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        $source = $input->getArgument('source');
        $recursive = !!$input->getOption('recursive');

        $files = $fs->files($source, $recursive);

        $hash2path = $path2hash = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $hash = $fs->hash($file);
            $path = $file->getRealPath();

            $hash2path[$hash][] = $path;
            $path2hash[$path] = $hash;
        }

        $fs->dumpFile($source . DIRECTORY_SEPARATOR . '/photosort_hash2path.json', json_encode($hash2path, JSON_PRETTY_PRINT));
        $fs->dumpFile($source . DIRECTORY_SEPARATOR . '/photosort_path2hash.json', json_encode($path2hash, JSON_PRETTY_PRINT));
    }
}