<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @internal
 */
function isMapEntry(MessageDescriptor $descriptor): bool
{
    return $descriptor->options?->mapEntry === true;
}
