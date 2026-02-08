<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class MapDescriptor
{
    public function __construct(
        public FieldDescriptor $key,
        public FieldDescriptor $value,
    ) {}
}
