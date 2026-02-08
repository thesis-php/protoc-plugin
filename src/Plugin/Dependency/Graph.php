<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

/**
 * @api
 */
final readonly class Graph
{
    /**
     * @param array<string, Type> $types
     */
    public function __construct(
        private array $types,
    ) {}

    public function get(string $name): Type
    {
        return $this->types[$name] ?? throw new \RuntimeException("No type '{$name}' was found.");
    }
}
