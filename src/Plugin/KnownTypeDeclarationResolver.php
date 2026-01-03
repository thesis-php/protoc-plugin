<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Known;

/**
 * @api
 */
final readonly class KnownTypeDeclarationResolver implements TypeDeclarationResolver
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
        '.google.protobuf.NullValue' => Known\NullValue::class,
    ];

    /** @var array<string, TypeDeclaration> */
    private array $declarations;

    /**
     * @param array<string, class-string> $knownTypes
     */
    public function __construct(array $knownTypes = [])
    {
        $types = self::WELL_KNOWN_TYPES + $knownTypes;

        $this->declarations = array_merge(...array_map(
            static fn(string $type, string $class) => [$type => self::generateKnownTypeDeclaration($class)],
            array_keys($types),
            array_values($types),
        ));
    }

    #[\Override]
    public function supports(FieldDescriptor $field): bool
    {
        return $field->typeName !== null && isset($this->declarations[$field->typeName]);
    }

    #[\Override]
    public function resolve(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): TypeDeclaration {
        \assert($field->typeName !== null);
        \assert(isset($this->declarations[$field->typeName]));

        return $this->declarations[$field->typeName];
    }

    /**
     * @param class-string $namespace
     */
    private static function generateKnownTypeDeclaration(string $namespace): TypeDeclaration
    {
        $typeName = Naming::extract($namespace, -2);

        return new TypeDeclaration(
            phpType: $namespace,
            reflectionType: Literal::new('Reflection\ObjectT', [
                new Literal("{$typeName}::class"),
            ]),
            uses: ['Thesis\Protobuf\Known'],
            nullable: true,
            docType: $typeName,
        );
    }
}
