#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protoc;
use Thesis\Protoc\Io;
use Thesis\Protoc\Plugin;

$protobuf = Protoc\ProtobufEncoder::default();

$entrypoint = new Protoc\Entrypoint(
    new Plugin\Compiler($protobuf),
    $protobuf,
);

$entrypoint->run(
    new Io\ReadStreamInput(),
    new Io\WriteStreamOutput(),
);
