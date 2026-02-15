<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final class PathTable
{
    /** @var array<string, string> */
    private array $paths = [];

    public function addRelation(string $className, string $path): void
    {
        $this->paths[$className] = $path;
    }

    /**
     * @return array<string, list<string>>
     */
    public function groupByNamespace(): array
    {
        $groups = [];

        foreach ($this->paths as $fqcn => $namespace) {
            $ns = explode('/', $namespace)[0];

            $groups[$ns]['types'][] = $fqcn;
            $groups[$ns]['ns'][] = $namespace;
        }

        $dict = [];

        foreach ($groups as $group) {
            $ns = $this->lookupNamespace($group['ns']);
            $dict[$ns] = $group['types'];
        }

        return $dict;
    }

    /**
     * @param list<string> $namespaces
     */
    private function lookupNamespace(array $namespaces): string
    {
        if ($namespaces === []) {
            return '';
        }

        $parts = array_map(
            static fn(string $ns) => explode('/', $ns),
            $namespaces,
        );
        $min = min(array_map(\count(...), $parts));

        $common = [];

        for ($i = 0; $i < $min; ++$i) {
            $current = $parts[0][$i] ?? '';

            foreach ($parts as $ns) {
                if (($ns[$i] ?? '') !== $current) {
                    return implode('\\', $common);
                }
            }

            $common[] = $current;
        }

        return implode('\\', $common);
    }
}
