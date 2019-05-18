<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Tests\Compiler\OptionalParameter;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class HashMapCommand extends Command
{
    protected static $defaultName = 'photosort:hash-map';

    private $finder;

    private $filesystem;

    public function __construct(string $name = null)
    {
        $this->finder = new Finder();
        $this->filesystem = new Filesystem();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path.');
        $this->setHelp('Creates a hashmap file, which may help to find duplicate files quicker.');

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source root path');
        $this->addOption('output-path', null, InputOption::VALUE_OPTIONAL, 'Path to output JSON file (instead of command return)', null);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source-path');
        $outputPath = $input->getOption('output-path');

        $this->ensureSourcePath($source);
        $this->ensureOutputPath($outputPath);

        $files = $this->finder->files()->name('/\.jpe?g/')->in($source);

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
                $hash = sha1_file($file);

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

        $this->filesystem->dumpFile($outputFile, json_encode($result, JSON_PRETTY_PRINT));

        if ($output->isVerbose()) {
            $output->writeln('Result: ' . $outputFile);
        }
    }

    private function ensureOutputFile(?string $outputPath, $sourcePath)
    {
        if (is_null($outputPath)) {
            return $sourcePath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
        }

        if ($this->filesystem->exists($outputPath)) {
            $realpath = realpath($outputPath);

            if (is_dir($realpath)) {
                return $realpath . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
            }

            return $realpath;
        }

        $pathInfo = pathinfo($outputPath);

        // Is it just a filename?
        if ($pathInfo['basename'] == basename($outputPath) && $pathInfo['dirname'] == "." && $pathInfo['extension'] == 'json') {
            return '.' . DIRECTORY_SEPARATOR . $outputPath;
        }

        if (empty($pathInfo['filename']) && !empty($pathInfo['dirname']) && $pathInfo['dirname'] !== ".") {
            return $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
        }

        return '.' . DIRECTORY_SEPARATOR . 'photosort_hashmap.json';
    }

    private function ensureSourcePath(?string $sourcePath)
    {
        if (!$this->filesystem->exists($sourcePath)) {
            throw new IOException("The source directory does not exist or is not accessible.");
        }
    }

    private function ensureOutputPath(?string $outputPath = null)
    {
        if (is_null($outputPath)) {
            return;
        }

        if ($this->filesystem->exists($outputPath) && is_dir($outputPath)) {
            return;
        }

        if (!$this->filesystem->exists($outputPath) && $this->filesystem->exists(dirname($outputPath)) && is_dir(dirname($outputPath))) {
            return;
        }

        throw new IOException("The output directory does not exist or is not accessible.");
    }
}