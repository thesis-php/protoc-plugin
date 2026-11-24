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

        return '\\' . $this->resolve($path) . '\\' . Naming::pascalCase($name);
    }

    /**
     * Only the protobuf segments of the type path are normalised. The namespace of the file
     * is already a PHP namespace, possibly a user-defined one ("Foo_Bar\\baz"), so it is kept
     * verbatim: nested types must land in the very namespace the top-level ones are declared in.
     */
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
            return $this->namespace . '\\' . Naming::joinNamespace($typeNamespace);
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
