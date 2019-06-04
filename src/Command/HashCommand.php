<?php

namespace App\Command;

use App\Service\HashService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class HashCommand extends AppBaseCommand
{
    const HASHMAP_IMAGES = ['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'];
    const HASHMAP_OUTPUT_FILENAME = 'photosort_hashmap.json';

    protected static $defaultName = 'app:hash';

    private $hasher;

    // Args
    private $sourcePath;

    private $outputPath;

    private $imageSignature;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->hasher = $hashService;

        parent::__construct($filesystem);
    }

    protected function configure()
    {
        $this->setDescription('Creates a message digest JSON for every file in a given path recursively.');
        $this->setHelp('Creates a message digest JSON file, which may help to find duplicate files quicker. By default only files bigger than 1K are processed. When the PHP extension imagick is found image signatures are added to find duplicated images based on the pixel-stream.');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source root path');
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path to output JSON file (default: will write in source-path)', null);
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

        $finder = Finder::create()
            ->files()
            ->name(self::HASHMAP_IMAGES)
            ->size('> 1K')
            ->sortByName()
            ->in($this->sourcePath);

        $fileCount = "";
        if ($output->isVeryVerbose()) {
            $fileCount = $finder->count();

            $output->writeln($fileCount . " files found.");
        }

        $hashs = [];
        $i = 0;
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $result = $this->hasher->hashFile($this->imageSignature);
            $hashs[$filePath] = $result;

            if ($output->isVerbose()) {
                $output->writeln("MD: " . $result['sha1'] . " - " . $filePath);
            }
        }

        $outputFile = $this->ensureOutputFile();

        $this->writeJsonFile($outputFile, $hashs);

        if ($output->isVerbose()) {
            $output->writeln('Result: ' . $outputFile);
        }
    }

    private function ensureOutputFile()
    {
        if (is_null($this->outputPath)) {
            return $this->sourcePath . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
        }

        if ($this->filesystem->exists($this->outputPath)) {
            $realpath = realpath($this->outputPath);

            if (is_dir($realpath)) {
                return $realpath . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
            }

            return $realpath;
        }

        $pathInfo = pathinfo($this->outputPath);

        // Is it just a filename?
        if ($pathInfo['basename'] == basename($this->outputPath) && $pathInfo['dirname'] == "." && $pathInfo['extension'] == 'json') {
            return '.' . DIRECTORY_SEPARATOR . $this->outputPath;
        }

        if (empty($pathInfo['filename']) && !empty($pathInfo['dirname']) && $pathInfo['dirname'] !== ".") {
            return $pathInfo['dirname'] . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
        }

        return '.' . DIRECTORY_SEPARATOR . self::HASHMAP_OUTPUT_FILENAME;
    }

    private function persistArgs(InputInterface $input)
    {
        $this->sourcePath = $input->getArgument('source');
        $this->outputPath = $input->getOption('output');

        $this->ensureSourcePath();
        $this->ensureOutputPath();
    }

    private function ensureSourcePath()
    {
        if (!$this->filesystem->exists($this->sourcePath)) {
            throw new IOException("The source directory does not exist or is not accessible.");
        }
    }

    private function ensureOutputPath()
    {
        if (is_null($this->outputPath)) {
            return;
        }

        if ($this->filesystem->exists($this->outputPath) && is_dir($this->outputPath)) {
            return;
        }

        if (!$this->filesystem->exists($this->outputPath) && $this->filesystem->exists(dirname($this->outputPath)) && is_dir(dirname($this->outputPath))) {
            return;
        }

        throw new IOException("The output directory does not exist or is not accessible.");
    }
}