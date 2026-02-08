<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protoc\Plugin\Naming;

/**
 * @api
 */
final readonly class PhpNamespacer
{
    public function __construct(
        public string $namespace,
    ) {}

    public function create(string $path): PhpNamespace
    {
        $namespace = $this->namespace;

        $paths = explode('.', $path);
        $typeNamespace = \array_slice($paths, 0, \count($paths) - 1);
        if (\count($typeNamespace) > 0) {
            $namespace = Naming::joinNamespace([
                $namespace,
                ...$typeNamespace,
            ]);
        }

        return new PhpNamespace($namespace);
    }
}
