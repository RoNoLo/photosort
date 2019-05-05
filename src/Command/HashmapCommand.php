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
    protected static $defaultName = 'ps:hash-map';

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Recursive');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        $source = $input->getArgument('source');

        $files = $fs->files($source);

        $hashMap = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $hashMap[$fs->hash($file)] = $file->getPathname();
        }

        $fs->dumpFile($source . DIRECTORY_SEPARATOR . '/photosort_hash2path.json', json_encode($hashMap, JSON_PRETTY_PRINT));
        $fs->dumpFile($source . DIRECTORY_SEPARATOR . '/photosort_path2hash.json', json_encode(array_flip($hashMap), JSON_PRETTY_PRINT));
    }
}