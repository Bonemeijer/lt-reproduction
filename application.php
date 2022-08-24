#!/usr/bin/env php
<?php

declare(strict_types = 1);

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use Reproduction\Command\Pixel6DimensionsReproduction;

$dotenv = new Dotenv();
$dotenv->usePutenv(true);
$dotenv->load(__DIR__.'/.env.dist', __DIR__.'/.env');

$application = new Application();
$application->add(new Pixel6DimensionsReproduction());

$application->run();
