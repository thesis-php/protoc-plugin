<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Type;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Known;
use Thesis\Protoc\Plugin\FieldDescriptor;
use Thesis\Protoc\Plugin\GrpcTypeDeclaration;
use Thesis\Protoc\Plugin\GrpcTypeDeclarationResolver;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\ProtoTypeDeclaration;
use Thesis\Protoc\Plugin\ProtoTypeDeclarationResolver;

/**
 * @api
 */
final readonly class KnownTypeDeclarationResolver implements
    ProtoTypeDeclarationResolver,
    GrpcTypeDeclarationResolver
{
    /** @var array<string, class-string> */
    private const array WELL_KNOWN_TYPES = [
        '.google.protobuf.Any' => Known\Any::class,
        '.google.protobuf.Api' => Known\Api::class,
        '.google.protobuf.Timestamp' => Known\Timestamp::class,
        '.google.protobuf.Duration' => Known\Duration::class,
        '.google.protobuf.Empty' => Known\EmptyObject::class,
        '.google.protobuf.Struct' => Known\Struct::class,
        '.google.protobuf.Value' => Known\Value::class,
        '.google.protobuf.NullValue' => Known\NullValueKind::class,
    ];

    /** @var array<non-empty-string, class-string> */
    private array $types;

    /**
     * @param array<non-empty-string, class-string> $knownTypes
     */
    public function __construct(array $knownTypes = [])
    {
        $this->types = self::WELL_KNOWN_TYPES + $knownTypes;
    }

    #[\Override]
    public function resolveProtoType(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ?ProtoTypeDeclaration {
        if ($field->typeName === null) {
            return null;
        }

        $class = $this->types[$field->typeName] ?? null;

        if ($class === null) {
            return null;
        }

        $name = Naming::extract($class, -2);

        return new ProtoTypeDeclaration(
            phpType: $class,
            reflectionType: Literal::new('Reflection\ObjectT', [
                new Literal("{$name}::class"),
            ]),
            uses: ['Thesis\Protobuf\Known'],
            nullable: true,
            docType: $name,
        );
    }

    #[\Override]
    public function resolveGrpcType(string $fqcn): ?GrpcTypeDeclaration
    {
        $class = $this->types[$fqcn] ?? null;

        if ($class === null) {
            return null;
        }

        $name = Naming::extract($class, -2);

        return new GrpcTypeDeclaration(
            name: $name,
            use: 'Thesis\Protobuf\Known',
        );
    }
}
