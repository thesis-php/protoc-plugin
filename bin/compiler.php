#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (is_string($configuredMemoryLimit = getenv('THESIS_PLUGIN_MEMORY_LIMIT'))) {
    @ini_set('memory_limit', $configuredMemoryLimit);
} else {
    $memoryLimitBytes = ini_parse_quantity(ini_get('memory_limit'));

    // We should increase memory_limit if it is lower than 512M.
    if ($memoryLimitBytes !== -1 && $memoryLimitBytes < 512 * 1_024 * 1_024) {
        @ini_set('memory_limit', '512M');
    }
}

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc;
use Thesis\Protoc\Io;
use Thesis\Protoc\Plugin;

$encoder = Encoder\Builder::buildDefault();
$decoder = Decoder\Builder::buildDefault();

try {
    // The relocation table shipped with the plugin: it tells under which php namespaces
    // the well known packages are generated and referenced.
    $types = Plugin\Mapping\TypeMap::fromFile(__DIR__ . '/../map.json');
} catch (Protoc\ProtocException $e) {
    fwrite(STDERR, "{$e->getMessage()}\n");

    exit(1);
}

$entrypoint = new Protoc\Entrypoint(
    new Plugin\Compiler($encoder, $types),
    $encoder,
    $decoder,
);

$entrypoint->run(
    new Io\ReadStreamInput(),
    new Io\WriteStreamOutput(),
);
