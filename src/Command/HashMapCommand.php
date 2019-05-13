<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class HashMapCommand extends Command
{
    protected static $defaultName = 'hash-map';

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path');
        $this->setHelp('Creates two hashmap files, which may help to find duplicate files quicker.');

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
        $emptyHash = null;
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            try {
                $path = $file->getRealPath();
                $hash = $fs->hash($file);

                if ($file->getSize() === 0) {
                    $emptyHash = $hash;
                }

                if ($output->isVerbose()) {
                    $output->writeln('Hashed: ' . $path);
                }

                $hash2path[$hash][] = $path;
                $path2hash[$path] = $hash;
            } catch (IOException $e) {
                if ($output->isVeryVerbose()) {
                    $output->writeln('IO Error: ' . $e->getMessage());
                }
            }
        }

        $result['source'] = realpath($source);
        $result['created'] = date('r');
        $result['empty_hash'] = $emptyHash;
        $result['hashs'] = $hash2path;
        $result['paths'] = $path2hash;

        $fs->dumpFile($source . DIRECTORY_SEPARATOR . '/photosort_hashmap.json', json_encode($result, JSON_PRETTY_PRINT));
    }
}