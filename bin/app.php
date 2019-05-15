#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

define ('APP_PATH', __DIR__);

use RoNoLo\PhotoSort\Command\AnalyseDuplicatesCommand;
use RoNoLo\PhotoSort\Command\FindDuplicatesCommand;
use RoNoLo\PhotoSort\Command\HashMapCommand;
use RoNoLo\PhotoSort\Command\PhotoSortCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new HashMapCommand());
$application->add(new FindDuplicatesCommand());
$application->add(new AnalyseDuplicatesCommand());
// $application->add(new PhotoSortCommand());

$application->run();