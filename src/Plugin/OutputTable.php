<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;

/**
 * Remembers the .proto file each generated class is written for. Different packages are normally generated into
 * different directories, but the src_path option puts every namespace into one directory and the php_namespace
 * option (as well as one php_namespace file option shared by several packages) puts every package into one namespace.
 * Two classes of different packages may then end up in one file, which is reported here instead of protoc's
 * "Tried to write the same file twice".
 *
 * Two classes of a single package written to one file are a naming collision of another kind and are left to protoc.
 *
 * @internal
 */
final class OutputTable
{
    /** @var array<string, array{string, string, string}> source, package and namespace of the file a path is written for */
    private array $owners = [];

    public function __construct(
        private readonly CompilerOptions $options,
    ) {}

    /**
     * @param iterable<CodeGeneratorResponse\File> $files the classes generated for $source
     * @param string $source the .proto file the classes are generated for
     * @param string $namespace the PHP namespace of that file
     * @return iterable<CodeGeneratorResponse\File> $files as they are
     * @throws CodeCannotBeGenerated
     */
    public function claim(
        iterable $files,
        string $source,
        Parser\FileDescriptor $proto,
        string $namespace,
    ): iterable {
        foreach ($files as $file) {
            $this->claimFile((string) $file->name, $source, $proto->package ?? '', $namespace);

            yield $file;
        }
    }

    /**
     * @throws CodeCannotBeGenerated
     */
    private function claimFile(string $path, string $source, string $package, string $namespace): void
    {
        if (!isset($this->owners[$path])) {
            $this->owners[$path] = [$source, $package, $namespace];

            return;
        }

        [$ownerSource, $ownerPackage, $ownerNamespace] = $this->owners[$path];

        if ($ownerPackage === $package && $ownerNamespace === $namespace) {
            return;
        }

        throw new CodeCannotBeGenerated(\sprintf(
            'Files %s and %s both generate %s.%s',
            self::describe($ownerSource, $ownerPackage, $ownerNamespace),
            self::describe($source, $package, $namespace),
            $path,
            $this->hint($ownerNamespace, $namespace),
        ));
    }

    private function hint(string $ownerNamespace, string $namespace): string
    {
        if ($ownerNamespace !== $namespace && $this->options->srcPath !== null) {
            return " The src_path={$this->options->srcPath} option puts the classes of every namespace into one directory, generate these packages in separate protoc runs with their own src_path.";
        }

        if ($ownerNamespace === $namespace && $namespace === $this->options->phpNamespace) {
            return " The php_namespace={$namespace} option puts the classes of every package into one namespace, generate these packages in separate protoc runs with their own php_namespace.";
        }

        return '';
    }

    private static function describe(string $source, string $package, string $namespace): string
    {
        return \sprintf(
            '%s (%s, namespace %s)',
            $source,
            $package === '' ? 'no package' : "package {$package}",
            $namespace,
        );
    }
}
