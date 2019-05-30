<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DeleteDuplicatesCommand extends Command
{
    protected static $defaultName = 'app:delete-duplicates';

    private $filesystem;

    /** @var OutputInterface */
    private $output;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Deletes duplicated files.');
        $this->setHelp('Deletes duplicated files in a interactive fashion. The duplicates should come from a photosort_duplicates.json file.');

        $this->addArgument('source-file', InputArgument::REQUIRED, 'Duplicates JSON file.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

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