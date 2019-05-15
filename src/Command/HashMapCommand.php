<?php

namespace RoNoLo\PhotoSort\Command;

use RoNoLo\PhotoSort\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

class HashMapCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'hash-map';

    private $fs;

    public function __construct(string $name = null)
    {
        $this->fs = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path');
        $this->setHelp('Creates two hashmap files, which may help to find duplicate files quicker.');

        $this->addArgument('source', InputArgument::REQUIRED, 'Source directory');
        $this->addArgument('output-path', InputArgument::OPTIONAL, 'Path to output file', null);
        $this->addOption('recursive', 'r', InputOption::VALUE_OPTIONAL, 'Recursive', true);
        // $this->addOption('file-extensions', 'e', InputOption::VALUE_OPTIONAL, 'List of extensions to process', '\.jpe?g');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getC

        try {
            $source = $input->getArgument('source');
            $outputPath = $input->getArgument('output-path');
            $recursive = !!$input->getOption('recursive');
//            $fileExtensions = $input->getOption('file-extensions');
//            $fileExtensions = $this->ensureFileExtensions($fileExtensions);

            $finder = new Finder();

            $files = $finder->files()->name('/\.jpe?g/')->in($source);

            $hash2path = $path2hash = [];
            $emptyHash = null;
            $errors = [];
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                try {
                    $path = $file->getRealPath();
                    $hash = $this->fs->hash($file);

                    if ($file->getSize() === 0) {
                        $emptyHash = $hash;
                    }

                    if ($output->isVerbose()) {
                        $output->writeln('Hashed: ' . $hash . ': ' . $path);
                    }

                    $hash2path[$hash][] = $path;
                    $path2hash[$path] = $hash;
                } catch (IOException $e) {
                    if ($output->isVeryVerbose()) {
                        $output->writeln('IO Error: ' . $e->getMessage());
                    }
                    $errors[$path] = $e->getMessage();
                } catch (\Exception $e) {
                    if ($output->isVeryVerbose()) {
                        $output->writeln('Error: ' . $e->getMessage());
                    }
                    $errors[$path] = $e->getMessage();
                }
            }
            $result['source'] = realpath($source);
            $result['created'] = date('r');
            $result['empty_hash'] = $emptyHash;
            $result['errors'] = $errors;
            $result['hashs'] = $hash2path;
            $result['paths'] = $path2hash;
            $outputFile = $this->ensureOutputFile($outputPath, $source);
            $this->fs->dumpFile($outputFile, json_encode($result, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            die ("Error: " . $e->getMessage());
        }
    }

    private function ensureOutputFile(?string $outputPath, $sourcePath)
    {
        if (is_null($outputPath)) {
            return $sourcePath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
        }

        if ($this->fs->exists($outputPath)) {
            $realpath = realpath($outputPath);

            if (is_dir($realpath)) {
                return $realpath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
            }

            return $realpath;
        }

        $pathInfo = pathinfo($outputPath);

        // Is it just a filename?
        if ($pathInfo['basename'] == $outputPath && $pathInfo['extension'] == 'json') {
            return APP_PATH . DIRECTORY_SEPARATOR . $outputPath;
        }

        if (empty($pathInfo['filename']) && !empty($pathInfo['dirname']) && $pathInfo['dirname'] !== ".") {
            return $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
        }

        return APP_PATH . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
    }

//    private function ensureFileExtensions(?string $fileExtensions)
//    {
//        if (is_null($fileExtensions)) {
//            throw new \InvalidArgumentException("No file extensions were given to process. Use `*` to include all file types.");
//        }
//
//        if (is_string($fileExtensions) && !empty($fileExtensions)) {
//            if ($fileExtensions === '*') {
//                return [];
//            }
//        }
//
//        $parts = explode(',', $fileExtensions);
//        $parts = array_map('trim', $parts);
//        $parts = array_filter($parts);
//
//        return $parts;
//    }
}