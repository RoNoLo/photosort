#!/usr/bin/env php
<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require __DIR__ . '/../vendor/autoload.php';

use RoNoLo\PhotoSort\Command\FindDuplicatesCommand;
use RoNoLo\PhotoSort\Command\HashMapCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new HashMapCommand());
$application->add(new FindDuplicatesCommand());

$application->run();