<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protoc\Plugin\Mapping\TypeMap;

/**
 * Single source of truth for the PHP namespace a protobuf file is generated into,
 * shared by the compiler (which declares the classes) and by the dependency registry
 * (which references them) so that a declaration and a reference can never diverge.
 *
 * @api
 */
final readonly class NamespaceResolver
{
    public function __construct(
        private TypeMap $types,
        private CompilerOptions $options,
    ) {}

    /**
     * @return ?non-empty-string the namespace of the file or null when it cannot be determined
     */
    public function file(Parser\FileDescriptor $descriptor): ?string
    {
        $package = $descriptor->package;

        // A relocation rule wins over any php_namespace: types are moved exactly because
        // their default namespace is already occupied by another runtime.
        if ($package !== null && $package !== '') {
            $rewritten = $this->types->rewritePackage($package);

            if ($rewritten !== null) {
                return self::toPhpNamespace($rewritten);
            }
        }

        if ($this->options->phpNamespace !== null) {
            return $this->options->phpNamespace;
        }

        $phpNamespace = $descriptor->options?->phpNamespace;
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        if ($package !== null && $package !== '') {
            return self::toPhpNamespace($package);
        }

        return null;
    }

    /**
     * @param string $type fully qualified protobuf type name, with or without the leading dot
     * @return ?non-empty-string fully qualified php class name, or null when the type is not relocated
     */
    public function type(string $type): ?string
    {
        $rewritten = $this->types->rewriteType($type);

        return $rewritten === null ? null : '\\' . self::toPhpNamespace($rewritten);
    }

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private static function toPhpNamespace(string $name): string
    {
        /** @var non-empty-string */
        return Naming::joinNamespace(explode('.', $name));
    }
}
