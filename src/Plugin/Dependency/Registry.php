<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

use Thesis\Protoc\Plugin\CompilerOptions;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final class Registry
{
    /** @var array<string, Index> */
    private array $index = [];

    /** @var array<string, Graph> */
    private array $graph = [];

    /** @var array<string, list<Index>> */
    private array $fileIndexes = [];

    public function __construct(Parser\Request $request, CompilerOptions $options)
    {
        $this->createIndex($request, $options);
        $this->mergeIndex($request);
        $this->createDependencyGraph($request);
    }

    public function graph(string $name): Graph
    {
        return $this->graph[$name] ?? throw new \RuntimeException("Graph for file {$name} does not exist.");
    }

    private function createIndex(Parser\Request $request, CompilerOptions $options): void
    {
        foreach ($request->descriptors as $name => $descriptor) {
            $types = iterator_to_array(
                $this->createFileIndex(
                    proto: $descriptor,
                    package: $descriptor->package === null ? '.' : ".{$descriptor->package}.",
                ),
                preserve_keys: false,
            );

            $types = array_combine(
                $types,
                array_fill(0, \count($types), 1),
            );

            $package = $descriptor->package ?? '.';
            $namespace = $options->phpNamespace ?? $descriptor->options->phpNamespace ?? Naming::namespace($package);

            $this->index[$name] = new Index(
                $types,
                $namespace,
                $package,
            );
        }
    }

    private function mergeIndex(Parser\Request $request): void
    {
        foreach ($request as $name => $descriptor) {
            $indexes = [];

            foreach ([$name, ...$descriptor->dependencies] as $dep) {
                if (isset($this->index[$dep])) {
                    $indexes = [
                        ...$indexes,
                        $this->index[$dep],
                    ];
                }
            }

            $this->fileIndexes[$name] = $indexes;
        }
    }

    private function createDependencyGraph(Parser\Request $request): void
    {
        foreach ($request->descriptors as $name => $proto) {
            $this->graph[$name] = new Graph(
                iterator_to_array(
                    $this->createFileDependencyGraph($proto),
                ),
            );
        }
    }

    /**
     * @return iterable<string, Type>
     */
    private function createFileDependencyGraph(Parser\FileDescriptor $proto): iterable
    {
        foreach ($proto->messages as $message) {
            yield from $this->createTypeDependencyGraph($message, $proto);
        }

        foreach ($proto->services as $service) {
            yield from $this->createServiceDependencyGraph($service, $proto);
        }
    }

    /**
     * @return iterable<string, Type>
     */
    private function createTypeDependencyGraph(Parser\MessageDescriptor $descriptor, Parser\FileDescriptor $proto): iterable
    {
        $package = $proto->package !== null ? ".{$proto->package}." : '.';

        $typeNames = [
            "{$package}{$descriptor->name}",
        ];

        foreach ($descriptor->fields as $field) {
            $typeName = $field->map !== null ? $field->map->value->typeName : $field->typeName;

            if ($typeName !== null && $typeName !== '') {
                $typeNames = [
                    ...$typeNames,
                    $typeName,
                ];
            }
        }

        yield from $this->extractTypes($typeNames, $proto->name);

        foreach ($descriptor->messages as $type) {
            yield from $this->createTypeDependencyGraph($type, $proto);
        }
    }

    /**
     * @return iterable<string, Type>
     */
    private function createServiceDependencyGraph(Parser\ServiceDescriptor $descriptor, Parser\FileDescriptor $proto): iterable
    {
        $typeNames = [];

        foreach ($descriptor->methods as $method) {
            $typeNames = [
                ...$typeNames,
                $method->inType,
                $method->outType,
            ];
        }

        yield from $this->extractTypes($typeNames, $proto->name);
    }

    /**
     * @return iterable<string>
     */
    private function createFileIndex(Parser\FileDescriptor $proto, string $package): iterable
    {
        foreach ($proto->enums as $enum) {
            yield "{$package}{$enum->name}";
        }

        foreach ($proto->messages as $message) {
            yield from $this->createDescriptorIndex($message, $package);
        }
    }

    /**
     * @return iterable<string>
     */
    private function createDescriptorIndex(Parser\MessageDescriptor $descriptor, string $name): iterable
    {
        yield $name = "{$name}{$descriptor->name}";

        foreach ($descriptor->messages as $it) {
            yield from $this->createDescriptorIndex($it, "{$name}.");
        }

        foreach ($descriptor->enums as $it) {
            yield "{$name}.{$it->name}";
        }
    }

    /**
     * @param list<string> $typeNames
     * @return iterable<string, Type>
     */
    private function extractTypes(array $typeNames, string $proto): iterable
    {
        foreach ($typeNames as $typeName) {
            foreach ($this->fileIndexes[$proto] ?? [] as $index) {
                if (!isset($index->types[$typeName])) {
                    continue;
                }

                $class = Naming::namespace(substr($typeName, \strlen($index->package) + 2));
                $fqcn = "\\{$index->namespace}\\{$class}";

                yield $typeName => new Type(
                    $fqcn,
                    $class,
                );
            }
        }
    }
}
