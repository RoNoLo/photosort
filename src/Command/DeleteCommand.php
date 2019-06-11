<?php

namespace App\Command;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class DuplicatesDeleteCommand
 *
 * @todo: Check if duplicates exist, so you could continue the process later without redo duplicates
 *
 * @package App\Command
 */
class DeleteCommand extends AppBaseCommand
{
    const DELETEDUPLICATES_ALLOWED_EXTENSIONS = ['jpg', 'jpeg'];
    const DELETEDUPLICATES_OUTPUT_FILENAME = 'photosort_deletes.json';

    protected static $defaultName = 'app:delete';

    /** @var string */
    private $sourceFile;

    /** @var string */
    private $recycleBinPath;

    /** @var array */
    private $log;

    protected function configure()
    {
        $this->setDescription('Deletes duplicated files.');
        $this->setHelp("Deletes duplicated files in a interactive fashion.\nThe duplicates should come from a photosort_duplicates.json file created by the app:dups command.\nWith the option --no-interaction always the first file in the list of candidates will be kept, the other will be deleted or moved to the recycle-bin (option --recycle-bin).");

        $this->addArgument('source-file', InputArgument::REQUIRED, 'Path to duplicates JSON file (created by app:dups).');
        $this->addOption('recycle-bin', 'b', InputOption::VALUE_OPTIONAL, 'Instead of deleting the files will be moved to that recycle directory.', null);
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

        $this->deleteDuplicates($data);

        $this->writeJsonFile(dirname($this->sourceFile) . DIRECTORY_SEPARATOR . self::DELETEDUPLICATES_OUTPUT_FILENAME, $this->log);
    }

    private function deleteDuplicates($data)
    {
        $helper = $this->getHelper('question');

        if ($this->input->getOption('no-interaction')) {
            $progressBar = new ProgressBar($this->output, count($data));
        }

        foreach ($data as $files) {
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

            $filesToDelete = array_diff($files, [$fileToKeep]);

            if ($this->input->getOption('no-interaction')) {
                $progressBar->advance();
            }

            $this->log['keeped'][] = $fileToKeep;

            if ($this->output->isVerbose()) {
                $this->output->writeln("File to keep: " . $fileToKeep);
            }

            $this->deleteFiles($filesToDelete);
        }

        if ($this->input->getOption('no-interaction')) {
            $progressBar->finish();
        }
    }

    private function deleteFiles($files)
    {
        if ($this->recycleBinPath) {
            $this->moveFilesToRecycleBin($files);

            return;
        }

        foreach ($files as $file) {
            $this->log['deleted'][] = $file;
            $this->deleteFile($file);
        }
    }

    /**
     * Because the Filesystem->remove() function cannot be limited to
     * a certain type and will recursively remove what it get, I will - for safety -
     * add some tests in advance to prevent data loss.
     *
     * Deletes:
     * - single files
     * - with the known extension
     * - is not a symbolic link
     * - file has to exist beforehand
     *
     * @param $filePath
     */
    private function deleteFile(string $filePath)
    {
        if (!$this->filesystem->exists($filePath)) {
            return;
        }

        $fileInfo = pathinfo($filePath);

        if (!in_array(strtolower($fileInfo['extension']), self::DELETEDUPLICATES_ALLOWED_EXTENSIONS)) {
            return;
        }

        if (is_link($filePath)) {
            return;
        }

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("Deleting: " . $filePath);
        }

        // $this->filesystem->remove($filePath);
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourceFile = $input->getArgument('source-file');
        $this->recycleBinPath = $input->getOption('recycle-bin');

        $this->ensureDuplicatesSourceExists();
        $this->ensureRecycleBin();
    }

    private function ensureDuplicatesSourceExists()
    {
        if (!$this->filesystem->exists($this->sourceFile)) {
            throw new InvalidArgumentException("Source duplicates file does not exists.");
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
            $this->deleteFile($file);

            if ($this->output->isVeryVerbose()) {
                $this->output->writeln("Moving to recycle bin: " . $file . " to " . $recycleFilePath);
            }

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

        throw new IOException("It was not possible to find a free rename filename in 100 tries for file: `{$filePath}`.");
    }
}