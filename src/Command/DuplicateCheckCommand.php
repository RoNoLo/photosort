<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateCheckCommand extends Command
{
    protected static $defaultName = 'find-duplicates';

    protected function configure()
    {
        $this->setDescription('Finds duplicate files');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('delete-duplicates-from-source', 'd', InputOption::VALUE_OPTIONAL, 'Delete files were duplicates in destination folder sturcture were found', false);
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
        $destination = $input->getArgument('destination');

        $this->ensureSource($source);
        $this->ensureDestination($source);

        $delete = !!$input->getOption('delete-duplicates-from-source');

        $files = $fs->files($source, true);

        $fs->exists($destination)

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }


        }
    }

    private function ensureSource(?string $source)
    {

    }
}