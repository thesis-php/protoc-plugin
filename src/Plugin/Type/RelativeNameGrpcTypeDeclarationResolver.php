<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Type;

use Thesis\Protoc\Plugin\FileDescriptor;
use Thesis\Protoc\Plugin\GrpcTypeDeclaration;
use Thesis\Protoc\Plugin\GrpcTypeDeclarationResolver;
use Thesis\Protoc\Plugin\MessageDescriptor;
use Thesis\Protoc\Plugin\Naming;

/**
 * @internal
 */
final readonly class RelativeNameGrpcTypeDeclarationResolver implements GrpcTypeDeclarationResolver
{
    /** @var array<string, GrpcTypeDeclaration> */
    private array $types;

    /**
     * @param list<FileDescriptor> $protos
     */
    public function __construct(array $protos)
    {
        $relations = [];

        foreach ($protos as $proto) {
            $package = $proto->package === null ? '.' : ".{$proto->package}.";

            foreach ($proto->messages as $message) {
                foreach (self::parseTypes($package, $message) as $fqcn) {
                    $relations[$package][] = $fqcn;
                }
            }
        }

        $types = [];

        foreach ($relations as $prefix => $names) {
            foreach ($names as $name) {
                $type = substr($name, \strlen($prefix));
                $chunks = explode('.', $type);

                $types[$name] = new GrpcTypeDeclaration(
                    Naming::namespace($type),
                    Naming::namespace(ltrim($prefix . $chunks[0], '.')),
                );
            }
        }

        $this->types = $types;
    }

    #[\Override]
    public function resolveGrpcType(string $fqcn): ?GrpcTypeDeclaration
    {
        return $this->types[$fqcn] ?? null;
    }

    /**
     * @param non-empty-string $name
     * @return iterable<non-empty-string>
     */
    private static function parseTypes(string $name, MessageDescriptor $message): iterable
    {
        yield $name = "{$name}{$message->name}";

        foreach ($message->messages as $it) {
            yield from self::parseTypes("{$name}.", $it);
        }
    }
}
