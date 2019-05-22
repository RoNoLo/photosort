<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommand extends Command
{
    protected static $defaultName = 'photosort:find-duplicates';

    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Analyses the hashmap.');
        $this->setHelp('Analyses and filters the hashmap based on options');

        $this->addArgument('source-file', InputArgument::REQUIRED, 'Hashmap');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $sourceFile = $input->getArgument('source-file');

            $this->ensureSourceExists($sourceFile);

            $data = json_decode(file_get_contents(realpath($sourceFile)), JSON_PRETTY_PRINT);

            $result = $this->findDuplicates($data);

            $this->filesystem->dumpFile(dirname($sourceFile) . DIRECTORY_SEPARATOR . '/photosort_duplicates.json', json_encode($result, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            die ("Error: " . $e->getMessage());
        }
    }

    private function ensureSourceExists(?string $source)
    {
        if (!$this->filesystem->exists($source)) {
            throw new IOException("Source hashmap file does not exists.");
        }
    }
}