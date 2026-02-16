#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc;
use Thesis\Protoc\Io;
use Thesis\Protoc\Plugin;

$encoder = Encoder\Builder::buildDefault();
$decoder = Decoder\Builder::buildDefault();

$entrypoint = new Protoc\Entrypoint(
    new Plugin\Compiler($encoder),
    $encoder,
    $decoder,
);

$entrypoint->run(
    new Io\ReadStreamInput(),
    new Io\WriteStreamOutput(),
);
