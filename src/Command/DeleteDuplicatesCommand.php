<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DeleteDuplicatesCommand extends Command
{
    protected static $defaultName = 'app:delete-duplicates';

    private $filesystem;

    /** @var OutputInterface */
    private $output;

    /** @var InputInterface */
    private $input;

    private $log;

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
        $this->addOption('recycle-bin-path', 'b', InputOption::VALUE_OPTIONAL, 'Instead of deleting the files will be moved to that recycle directory', null);
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
        $this->input = $input;

        try {
            $sourceFile = $input->getArgument('source-file');
            $recycleBinPath = $input->getOption('recycle-bin-path');

            $this->ensureDuplicatesSourceExists($sourceFile);
            $this->ensureRecycleBin($recycleBinPath);

            $data = json_decode(file_get_contents(realpath($sourceFile)), JSON_PRETTY_PRINT);

            $result = $this->findDuplicates($data, $recycleBinPath);

            $this->filesystem->dumpFile(dirname($sourceFile) . DIRECTORY_SEPARATOR . '/photosort_duplicates.json', json_encode($result, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            die ("Error: " . $e->getMessage());
        }
    }

    private function findDuplicates($data, $recycleBinPath = null)
    {
        $helper = $this->getHelper('question');

        foreach ($data as $files) {
            $choices = [];
            foreach ($files as $i => $file) {
                $choices[$i] = $file;
            }
            $question = new ChoiceQuestion('Which image file do you want to KEEP?', $choices, '0');
            $question->setErrorMessage("File number %s is invalid");

            $fileKeep = $helper->ask($this->input, $this->output, $question);

            $this->deleteFilesExcept($files, $fileKeep, $recycleBinPath);
        }
    }

    private function deleteFilesExcept($files, $fileKeep, $recycleBinPath)
    {
        foreach ($files as $i => $file) {
            if ($file == $fileKeep) {
                continue;
            }

            if ($this->output->isVerbose()) {
                $this->output->writeln("Deleteing file: " . $file);
            }

            $this->delete($file);
        }
    }

    private function delete($file)
    {
        // $this->filesystem->remove($file);
        $this->output->writeln("Delete");
    }

    private function ensureDuplicatesSourceExists(?string $source)
    {
        if (!$this->filesystem->exists($source)) {
            throw new IOException("Source duplicates file does not exists.");
        }
    }

    private function ensureRecycleBin(?string $recycleBinPath = null)
    {
        if (is_null($recycleBinPath)) {
            return;
        }

        if (!$this->filesystem->exists($recycleBinPath)) {
            throw new IOException("The recycle bin path has to exist.");
        }
    }
}