<?php

declare(strict_types=1);

return Rector\Config\RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withParallel()
    ->withCache(__DIR__ . '/var/rector')
    ->withPhpSets()
    ->withSkip([
        // Generated code: must stay byte-for-byte what the plugin prints (see tests/tools/check-snapshots.sh).
        __DIR__ . '/tests/snapshots',
        Rector\Php80\Rector\Class_\StringableForToStringRector::class,
        Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class,
    ]);
