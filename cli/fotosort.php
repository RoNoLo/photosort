<?php

namespace RoNoLo\ImageSorter;

include_once __DIR__ .
    DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . 'vendor' .
    DIRECTORY_SEPARATOR . 'autoload.php';

$factory = new Factory();

$imageSorter = $factory->createImageSorter();
$imageSorter->setLogger(new Logger());

$imageSorter->run();