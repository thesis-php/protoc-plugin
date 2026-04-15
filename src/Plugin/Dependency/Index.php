<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

/**
 * @api
 */
final readonly class Index
{
    /**
     * @param array<string, ?string> $types map of fully-qualified type name to default value (zero case for enums or null for messages)
     */
    public function __construct(
        public array $types,
        public string $namespace,
        public string $package,
    ) {}
}
