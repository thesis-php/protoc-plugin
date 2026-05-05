<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class Index
{
    /**
     * @param array<string, Parser\EnumDescriptor|Parser\MessageDescriptor> $types map of fully-qualified type name to container type (enums or messages)
     */
    public function __construct(
        public array $types,
        public string $namespace,
        public string $package,
    ) {}
}
