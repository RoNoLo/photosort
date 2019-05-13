<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class AnalyseDuplicatesCommand extends Command
{
    protected static $defaultName = 'analyse-duplicates';

    private $fs;

    public function __construct(string $name = null)
    {
        $this->fs = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Analyses the hashmap.');
        $this->setHelp('Analyses and filters the hashmap based on options');

        $this->addArgument('source', InputArgument::REQUIRED, 'Hashmap');
        $this->addOption('only-duplicates', 'd', InputOption::VALUE_OPTIONAL, 'Remove all unique files', true);
        $this->addOption('remove-empty', 'e', InputOption::VALUE_OPTIONAL, 'Remove files which are empty', true);
        $this->addOption('remove-path-to-hash', 'p', InputOption::VALUE_OPTIONAL, 'Remove the path to hash section', false);
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
        $onlyDuplicates = !!$input->getOption('only-duplicates');
        $removeEmpty = !!$input->getOption('remove-empty');
        $removePathToHash = !!$input->getOption('remove-path-to-hash');

        $this->ensureSourceExists($source);

        $data = json_decode(file_get_contents(realpath($source)), JSON_PRETTY_PRINT);

        if ($removeEmpty) {
            if (!is_null($data['empty_hash'])) {
                if (isset($data['hashs'][$data['empty_hash']])) {
                    unset($data['hashs'][$data['empty_hash']]);
                }
            }
        }

        if ($removePathToHash) {
            $data['paths'] = [];
        }

        if ($onlyDuplicates) {
            foreach ($data['hashs'] as $hash => $files) {
                if (count($files) < 2) {
                    unset($data['hashs'][$hash]);
                }
            }
        }

        $fs->dumpFile(dirname($source) . DIRECTORY_SEPARATOR . '/photosort_hashmap_analysed.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    private function ensureSourceExists(?string $source)
    {
        if (!$this->fs->exists($source)) {
            throw new IOException("Source hashmap file does not exists.");
        }
    }
}