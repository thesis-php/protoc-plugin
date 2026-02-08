<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

/**
 * @api
 */
final readonly class Index
{
    /**
     * @param array<string, 1> $types
     */
    public function __construct(
        public array $types,
        public string $namespace,
        public string $package,
    ) {}
}
