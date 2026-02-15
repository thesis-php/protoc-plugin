<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Google\Protobuf\FieldDescriptorProto;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protoc\Plugin\Dependency;
use Thesis\Protoc\Plugin\NameIndex;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class ProtoGenerator
{
    private TypeDeclarationFactory $types;

    public function __construct(
        Dependency\Graph $graph,
        private NameIndex $index,
        private PhpNamespacer $namespacer,
        private ?string $syntax = null,
    ) {
        $this->types = new TypeDeclarationFactory($graph);
    }

    public function generateEnum(Parser\EnumDescriptor $enum): PhpNamespace
    {
        $namespace = $this->namespacer->create($enum->path);

        $enumName = Naming::pascalCase($enum->name);

        $this->index->addEnumType($enum->fqcn, "{$namespace->getName()}\\{$enumName}");

        $enumType = new EnumType($enumName)
            ->addComment('@api')
            ->addComment($enum->comment !== null ? "\n{$enum->comment}" : '')
            ->setType('int')
            ->setCases(array_map(
                static fn(Parser\EnumCaseDescriptor $case) => new EnumCase(Naming::secureEnumCase($case->name))
                    ->setValue($case->value)
                    ->setComment((string) $case->comment),
                $enum->cases,
            ));

        $namespace->add($enumType);

        return $namespace;
    }

    /**
     * @return iterable<string, PhpNamespace>
     */
    public function generateMessages(Parser\MessageDescriptor $message): iterable
    {
        yield $message->path => $this->generateMessage($message);

        $oneofByIndex = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null) {
                if (isset($message->oneofs[$field->oneOfIndex])) {
                    $oneOf = $message->oneofs[$field->oneOfIndex];

                    yield from $this->generateOneofVariant(
                        $message,
                        $oneOf,
                        $field,
                    );

                    $oneofByIndex[$field->oneOfIndex][] = $field;
                }
            }
        }

        foreach ($message->oneofs as $idx => $oneof) {
            $variants = $oneofByIndex[$idx] ?? [];

            if ($variants !== []) {
                yield from $this->generateOneof($message, $oneof, $variants);
            }
        }
    }

    /**
     * @param list<string> $implements
     */
    private function generateMessage(Parser\MessageDescriptor $message, array $implements = []): PhpNamespace
    {
        $namespace = $this->namespacer->create($message->path);

        $className = Naming::pascalCase($message->name);

        $this->index->addMessageType($message->fqcn, "{$namespace->getName()}\\{$className}");

        $classType = new ClassType($className)
            ->setFinal()
            ->setReadOnly()
            ->setImplements($implements)
            ->addComment('@api')
            ->addComment($message->comment !== null ? "\n{$message->comment}" : '');

        $namespace->add($classType);

        if ($message->fields === []) {
            return $namespace;
        }

        $namespace->addUse('Thesis\Protobuf\Reflection');

        $constructor = $classType->addMethod('__construct');

        $oneOfByIndex = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null) {
                $oneOfByIndex[$field->oneOfIndex][] = $field;

                continue;
            }

            $parameter = $constructor->addPromotedParameter(Naming::camelCase($field->name));

            $type = null;

            if ($field->map !== null) {
                $keyType = $this->types->create($field->map->key);
                $valueType = $this->types->create($field->map->value);

                $type = new TypeDeclaration(
                    phpType: 'Protobuf\Map',
                    reflectionType: Literal::new('Reflection\MapT', [$keyType->reflectionType, $valueType->reflectionType]),
                    nullable: false,
                    isMap: true,
                    docType: "Protobuf\\Map<{$keyType->resolvedType()}, {$valueType->resolvedType()}>",
                    default: Literal::new('Protobuf\Map'),
                    uses: ['Thesis\Protobuf'],
                );
            }

            $type ??= $this->types->create($field);

            foreach ($type->uses as $use) {
                $namespace->addUse($use);
            }

            $repeated = $field->label === FieldDescriptorProto\Label::LABEL_REPEATED && !$type->isMap;

            $nullable = ($type->nullable || $field->optional) && !$repeated;

            $phpType = ($nullable ? '?' : '') . ($repeated ? 'array' : $type->phpType);

            $reflectionType = $type->reflectionType;

            if ($repeated) {
                $parameters = [$reflectionType];

                // In proto2 all repeated fields are non-packed by default unless explicitly marked with [packed = true].
                if ($this->syntax === null) {
                    $parameters[] = $field->options?->packed === true;
                }

                $reflectionType = Literal::new('Reflection\ListT', $parameters);
            }

            $default = $nullable ? null : $type->default;

            // In proto2 (and only there) scalar fields can have default values set.
            if ($this->syntax === null && $field->defaultValue !== null && $field->type !== null) {
                $default = self::parseDefaultValue($field->type, $field->defaultValue);
            }

            $parameter
                ->setType($phpType)
                ->addAttribute('Reflection\Field', [
                    $field->number,
                    $reflectionType,
                ])
                ->setNullable($nullable)
                ->setDefaultValue($repeated ? [] : $default);

            if ($repeated || $field->comment !== null || $type->isMap) {
                $docType = $type->resolvedType();

                if ($repeated) {
                    $docType = "list<{$docType}>";
                }

                if ($nullable) {
                    $docType = "?{$docType}";
                }

                $comment = "@param {$docType} \${$parameter->getName()}";
                if ($field->comment !== null) {
                    $comment .= " {$field->comment}";
                }

                $constructor->addComment($comment);
            }
        }

        foreach ($oneOfByIndex as $idx => $variants) {
            if (!isset($message->oneofs[$idx])) {
                continue;
            }

            $oneOf = $message->oneofs[$idx];

            $oneOfName = Naming::pascalCase($oneOf->name);

            $constructor
                ->addPromotedParameter(Naming::camelCase($oneOf->name))
                ->setType(Naming::joinNamespace([
                    '',
                    $this->namespacer->namespace,
                    $message->path,
                    $oneOfName,
                ]))
                ->setNullable()
                ->setDefaultValue(null)
                ->addAttribute('Reflection\OneOf', [
                    array_map(
                        fn(Parser\FieldDescriptor $variant) => new Literal(
                            Naming::joinNamespace([
                                '',
                                $this->namespacer->namespace,
                                $message->path,
                                \sprintf("{$oneOfName}%s::class", Naming::pascalCase($variant->name)),
                            ]),
                        ),
                        $variants,
                    ),
                ]);
        }

        return $namespace;
    }

    /**
     * @param list<Parser\FieldDescriptor> $variants
     * @return iterable<string, PhpNamespace>
     */
    private function generateOneof(
        Parser\MessageDescriptor $message,
        Parser\OneOfDescriptor $oneof,
        array $variants,
    ): iterable {
        $interfaceName = Naming::pascalCase($oneof->name);

        $interfaceType = new InterfaceType($interfaceName)
            ->addComment('@api')
            ->addComment('@phpstan-sealed (')
            ->addComment(implode(" |\n", array_map(
                static fn(Parser\FieldDescriptor $variant) => \sprintf('  %s%s', $interfaceName, Naming::pascalCase($variant->name)),
                $variants,
            )))
            ->addComment(')');

        $path = "{$message->path}.{$interfaceType->getName()}";

        $namespace = $this->namespacer->create($path);

        $namespace->add($interfaceType);

        yield $path => $namespace;
    }

    /**
     * @return iterable<string, PhpNamespace>
     */
    private function generateOneofVariant(
        Parser\MessageDescriptor $message,
        Parser\OneOfDescriptor $oneof,
        Parser\FieldDescriptor $variant,
    ): iterable {
        $interfaceName = Naming::pascalCase($oneof->name);
        $className = \sprintf('%s%s', $interfaceName, Naming::pascalCase($variant->name));

        $descriptor = new Parser\MessageDescriptor(
            name: $className,
            fqcn: $message->fqcn,
            path: $path = "{$message->path}.{$className}",
            fields: [
                // Remove oneof index.
                new Parser\FieldDescriptor(
                    name: $variant->name,
                    number: $variant->number,
                    label: $variant->label,
                    type: $variant->type,
                    typeName: $variant->typeName,
                    comment: $variant->comment,
                    options: $variant->options,
                    optional: $variant->optional,
                ),
            ],
        );

        yield $path => $this->generateMessage($descriptor, [
            Naming::joinNamespace([
                '',
                $this->namespacer->namespace,
                $message->path,
                $interfaceName,
            ]),
        ]);
    }

    private static function parseDefaultValue(
        FieldDescriptorProto\Type $type,
        string $defaultValue,
    ): mixed {
        return match ($type) {
            FieldDescriptorProto\Type::TYPE_STRING,
            FieldDescriptorProto\Type::TYPE_BYTES => $defaultValue,
            FieldDescriptorProto\Type::TYPE_INT32,
            FieldDescriptorProto\Type::TYPE_SINT32,
            FieldDescriptorProto\Type::TYPE_UINT32,
            FieldDescriptorProto\Type::TYPE_FIXED32,
            FieldDescriptorProto\Type::TYPE_SFIXED32 => filter_var($defaultValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0,
            FieldDescriptorProto\Type::TYPE_INT64,
            FieldDescriptorProto\Type::TYPE_SINT64,
            FieldDescriptorProto\Type::TYPE_UINT64,
            FieldDescriptorProto\Type::TYPE_FIXED64,
            FieldDescriptorProto\Type::TYPE_SFIXED64 => Literal::new('\BcMath\Number', [
                filter_var($defaultValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0,
            ]),
            FieldDescriptorProto\Type::TYPE_BOOL => filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN),
            FieldDescriptorProto\Type::TYPE_FLOAT,
            FieldDescriptorProto\Type::TYPE_DOUBLE => filter_var($defaultValue, FILTER_VALIDATE_FLOAT),
            default => null,
        };
    }
}
