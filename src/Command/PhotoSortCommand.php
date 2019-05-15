<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PhotoSortCommand extends Command
{
    protected static $defaultName = 'photo-sort';

    private $fs;

    public function __construct(string $name = null)
    {
        $this->fs = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Copies or moves images into a folder structure');
        $this->setHelp('This command allows you to create a user...');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('destination', InputArgument::REQUIRED, 'Destination directory root');
        $this->addOption('copy', 'c', InputOption::VALUE_OPTIONAL, 'Copy files instead of moving', false);
        $this->addOption('use-hashmap', 'h', InputOption::VALUE_OPTIONAL, 'Use hashmap file to find duplicates', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');
        $copy = !!$input->getOption('copy');
        $useHashMap = !!$input->getOption('use-hashmap');

        $files = $this->fs->files($source, true);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $photoDate = $file->getMTime();
        }
    }
}