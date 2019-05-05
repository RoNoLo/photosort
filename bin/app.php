#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use RoNoLo\PhotoSort\Command\HashmapCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new HashmapCommand());

$application->run();