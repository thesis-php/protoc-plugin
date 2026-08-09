<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protoc\Plugin\Mapping\TypeMap;
use Thesis\Protoc\Plugin\Naming;

/**
 * @api
 */
final readonly class PhpNamespacer
{
    public function __construct(
        public string $namespace,
        private TypeMap $types = new TypeMap(),
        private ?string $package = null,
    ) {}

    public function create(string $path): PhpNamespace
    {
        return new PhpNamespace($this->resolve($path));
    }

    public function fqcn(string $path): string
    {
        $paths = explode('.', $path);
        $name = $paths[\count($paths) - 1];

        return Naming::joinNamespace([
            '',
            $this->resolve($path),
            $name,
        ]);
    }

    private function resolve(string $path): string
    {
        $rewritten = $this->rewrite($path);

        if ($rewritten !== null) {
            $segments = explode('.', $rewritten);
            array_pop($segments);

            return Naming::joinNamespace($segments);
        }

        $paths = explode('.', $path);
        $typeNamespace = \array_slice($paths, 0, \count($paths) - 1);

        if (\count($typeNamespace) > 0) {
            return Naming::joinNamespace([
                $this->namespace,
                ...$typeNamespace,
            ]);
        }

        return $this->namespace;
    }

    /**
     * @return ?non-empty-string
     */
    private function rewrite(string $path): ?string
    {
        $type = $this->package !== null && $this->package !== '' ? "{$this->package}.{$path}" : $path;

        return $this->types->rewriteType($type);
    }
}
