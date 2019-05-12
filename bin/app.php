#!/usr/bin/env php
<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require __DIR__ . '/../vendor/autoload.php';

use RoNoLo\PhotoSort\Command\DuplicateCheckCommand;
use RoNoLo\PhotoSort\Command\HashmapCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new HashmapCommand());
$application->add(new DuplicateCheckCommand());

$application->run();