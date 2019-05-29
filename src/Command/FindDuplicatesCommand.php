<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FindDuplicatesCommand extends Command
{
    protected static $defaultName = 'photosort:find-duplicates';

    private $filesystem;

    private $hasher;

    /** @var OutputInterface */
    private $output;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->filesystem = $filesystem;
        $this->hasher = $hashService;

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

    private function findDuplicates($data)
    {
        $found = [];
        $files = array_keys($data);
        $fileCount = count($files);

        $results = [];
        for ($i = 0; $i < $fileCount; $i++) {
            if ($i + 1 > $fileCount) {
                break;
            }

            $tmp = [];
            $tmp[] = $files[$i];


            for ($j = $i + 1; $j < $fileCount; $j++) {
                if (in_array($files[$i], $found)) {
                    continue;
                }
                $result = $this->hasher->compareHashResults($data[$files[$i]], $data[$files[$j]]);

                if ($this->output->isVerbose()) {
                    $this->output->writeln("Checking file: " . $files[$i] . " vs. " . $files[$j] . " result is: " . ($result ? "same" : "different"));
                }

                if ($result) {
                    $tmp[] = $files[$j];
                    $found[] = $files[$j];
                }
            }

            if (count($tmp) > 1) {
                $results[] = $tmp;
            }
        }

        return $results;
    }

    private function ensureSourceExists(?string $source)
    {
        if (!$this->filesystem->exists($source)) {
            throw new IOException("Source hashmap file does not exists.");
        }
    }
}