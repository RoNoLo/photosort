<?php

namespace App\Command;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Exception\IOException;

class DuplicatesDeleteCommand extends AppBaseCommand
{
    const DELETEDUPLICATES_OUTPUT_FILENAME = 'photosort_deletes.json';

    protected static $defaultName = 'app:delete';

    private $sourceFile;

    private $recycleBinPath;

    private $keepFirst = false;

    private $log;

    protected function configure()
    {
        $this->setDescription('Deletes duplicated files.');
        $this->setHelp('Deletes duplicated files in a interactive fashion. The duplicates should come from a photosort_duplicates.json file created by the app:dups command.');

        $this->addArgument('source-file', InputArgument::REQUIRED, 'Path to duplicates JSON file (created by app:dups).');
        $this->addOption('recycle-bin', 'b', InputOption::VALUE_OPTIONAL, 'Instead of deleting the files will be moved to that recycle directory.', null);
        $this->addOption('keep-first', 'y', InputOption::VALUE_NONE, 'This will always keep the first file and do not ask for input (WARNING!).');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->persistInput($input, $output);
        $this->persistArgs($input);

        $data = $this->readJsonFile($this->sourceFile);

        $this->findDuplicates($data);

        $this->writeJsonFile(dirname($this->sourceFile) . DIRECTORY_SEPARATOR . self::DELETEDUPLICATES_OUTPUT_FILENAME, $this->log);
    }

    private function findDuplicates($data)
    {
        $helper = $this->getHelper('question');

        foreach ($data as $files) {
            if ($this->keepFirst) {
                $fileToKeep = array_shift($files);
            } else {
                $choices = [];
                foreach ($files as $i => $file) {
                    $choices[$i] = $file;
                }
                $question = new ChoiceQuestion('Which image file do you want to KEEP?', $choices, '0');
                $question->setErrorMessage("File number %s is invalid");

                $fileToKeep = $helper->ask($this->input, $this->output, $question);

                if (is_null($fileToKeep) || empty($fileToKeep)) {
                    throw new \Exception("Something went wrong, with the input");
                }

                $files = array_diff($files, [$fileToKeep]);
            }

            $this->log['keeped'][] = $fileToKeep;

            if ($this->output->isVerbose()) {
                $this->output->writeln("File to keep: " . $fileToKeep);
            }

            $this->deleteFiles($files);
        }
    }

    private function deleteFiles($files)
    {
        if ($this->output->isVeryVerbose()) {
            foreach ($files as $file) {
                $this->output->writeln("Deleting: " . $file);
            }
        }

        if ($this->recycleBinPath) {
            $this->moveFilesToRecycleBin($files);

            return;
        }

        foreach ($files as $file) {
            $this->log['deleted'][] = $file;
        // $this->filesystem->remove($file);
        }
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourceFile = $input->getArgument('source-file');
        $this->recycleBinPath = $input->getOption('recycle-bin');
        $this->keepFirst = !!$input->getOption('keep-first');

        $this->ensureDuplicatesSourceExists();
        $this->ensureRecycleBin();
    }

    private function ensureDuplicatesSourceExists()
    {
        if (!$this->filesystem->exists($this->sourceFile)) {
            throw new IOException("Source duplicates file does not exists.");
        }
    }

    private function ensureRecycleBin()
    {
        if (is_null($this->recycleBinPath)) {
            return;
        }

        if (!$this->filesystem->exists($this->recycleBinPath)) {
            throw new InvalidArgumentException("The recycle bin path does not exists.");
        }
    }

    private function moveFilesToRecycleBin(array $files)
    {
        $date = date('Ymd');

        foreach ($files as $file) {
            $filename = basename($file);

            $recycleFilePath = $this->recycleBinPath . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR . $filename;

            if ($this->filesystem->exists($recycleFilePath)) {
                $recycleFilePath = $this->renameDestinationFile($recycleFilePath);
            }

            $this->filesystem->copy($file, $recycleFilePath);
            // $this->filesystem->remove($file);

            $this->log['moved'][] = [
                'from' => $file,
                'to' => $recycleFilePath
            ];
        }
    }

    private function renameDestinationFile(string $filePath)
    {
        $breaker = 10000;

        $destinationFilePath = null;
        do {
            $pathinfo = pathinfo($filePath);

            $filename = $pathinfo['filename'] . '_' . (10000 - $breaker + 1);

            $destinationFilePath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $pathinfo['extension'];

            if (!$this->filesystem->exists($destinationFilePath)) {
                return $destinationFilePath;
            }

            $breaker--;
        } while ($breaker);

        throw new IO0Exception("It was not possible to find a free rename filename in 100 tries for file: `{$filePath}`.");
    }
}