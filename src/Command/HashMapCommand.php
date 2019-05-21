<?php

namespace App\Command;

use App\Service\HashService;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use Jenssegers\ImageHash\Implementations\BlockHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class HashMapCommand extends Command
{
    const IMAGES = ['*.jpg', '*.jpeg', '*.JPG', '*.JPEG'];

    protected static $defaultName = 'photosort:hash-map';

    private $filesystem;

    private $hasher;

    public function __construct(Filesystem $filesystem, HashService $hashService)
    {
        $this->filesystem = $filesystem;
        $this->hasher = $hashService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Creates an hashmap on every file in a path.');
        $this->setHelp('Creates a hashmap file, which may help to find duplicate files quicker.');

        $this->addArgument('source-path', InputArgument::REQUIRED, 'Source root path');
        $this->addOption('output-path', null, InputOption::VALUE_OPTIONAL, 'Path to output JSON file (instead of command return)', null);
        $this->addOption('calculate-image-content-hashs', null, InputOption::VALUE_OPTIONAL, 'Will also create image content hashes', false);
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
        $imageHashs = !!$input->getOption('calculate-image-content-hashs');

        $this->ensureSourcePath($source);
        $this->ensureOutputPath($outputPath);

        $finder = Finder::create()
            ->files()
            ->name(self::IMAGES)
            ->size('> 1K')
            ->in($source);

        $results = $this->hasher->hashFiles($finder, $imageHashs);

        $outputFile = $this->ensureOutputFile($outputPath, $source);

        $this->filesystem->dumpFile($outputFile, json_encode($results, JSON_PRETTY_PRINT));

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