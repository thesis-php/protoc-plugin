#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf;
use Thesis\Protobuf\Reflection;
use Thesis\Protoc;
use Thesis\Protoc\Io;
use Thesis\Protoc\Plugin;

$entrypoint = new Protoc\Entrypoint(
    new Plugin\Compiler(),
    new Protobuf\Serializer(),
    Reflection\Reflector::build(),
);

$entrypoint->run(
    new Io\ReadStreamInput(),
    new Io\WriteStreamOutput(),
);
