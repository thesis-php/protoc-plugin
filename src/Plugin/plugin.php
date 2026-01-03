<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Thesis\Package;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;

if (!\defined('Thesis\Protoc\Plugin\Internal\SUPPORTED_FEATURES')) {
    \define('Thesis\Protoc\Plugin\Internal\SUPPORTED_FEATURES', new Number(
        CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
        | CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value,
    ));
}

if (!\defined('Thesis\Protoc\Plugin\Internal\PLUGIN_VERSION')) {
    \define('Thesis\Protoc\Plugin\Internal\PLUGIN_VERSION', Package\version(Compiler::PLUGIN_NAME));
}

/**
 * @internal
 */
function isMapEntry(MessageDescriptor $descriptor): bool
{
    return $descriptor->options?->mapEntry === true;
}
