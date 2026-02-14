<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('thesis/package-version', [ErrorType::UNUSED_DEPENDENCY]);
