<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 * @template-implements \IteratorAggregate<array-key, DescriptorRegistryGroup>
 */
final class DescriptorTable implements \IteratorAggregate
{
    /** @var array<string, DescriptorRegistryGroup> */
    private array $groups = [];

    public function add(string $path, string $namespace): DescriptorRegistryGroup
    {
        return $this->groups[$path] ??= new DescriptorRegistryGroup($namespace, $path);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from array_values($this->groups);
    }
}
