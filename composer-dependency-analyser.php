<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();

return $config
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/bin', isDev: true)
    ->addPathToExclude(__DIR__ . '/tests');
