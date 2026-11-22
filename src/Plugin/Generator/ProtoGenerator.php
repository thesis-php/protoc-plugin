<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Google\Protobuf\Edition;
use Thesis\Google\Protobuf\FeatureSet;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Protobuf\Registry\File;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
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
        private Dependency\Graph $graph,
        private NameIndex $index,
        private PhpNamespacer $namespacer,
        private ?string $syntax = null,
        private ?Edition $edition = null,
    ) {
        $this->types = new TypeDeclarationFactory($this->graph);
    }

    /**
     * @throws CodeCannotBeGenerated
     */
    public function generateEnum(Parser\EnumDescriptor $enum): PhpNamespace
    {
        $namespace = $this->namespacer->create($enum->path);

        $enumName = Naming::pascalCase($enum->name);

        if ($enum->fqcn === '') {
            throw new CodeCannotBeGenerated('Enum name must not be empty.');
        }

        /** @var class-string $enumFqcn */
        $enumFqcn = "{$namespace->getName()}\\{$enumName}";

        $this->index->addEnumType(new File\EnumDescriptor(
            $enum->fqcn,
            $enumFqcn,
        ));

        $enumType = new EnumType($enumName)
            ->setType('int')
            ->addComment('@api');

        // With `option allow_alias = true` several values may share a number, while a PHP enum
        // cannot have two cases with the same value. The first value declared with a number becomes
        // the case, every later one becomes a constant referring to that case.
        /** @var array<int, string> $caseNames */
        $caseNames = [];
        /** @var array<string, int> $memberValues */
        $memberValues = [];

        foreach ($enum->cases as $case) {
            $name = Naming::secureEnumCase($case->name);

            // `class` and `class_` both end up as `class_`. protoc lets such names coexist only
            // as aliases of one number, so the second one is the very same member and is dropped.
            if (isset($memberValues[$name])) {
                if ($memberValues[$name] !== $case->value) {
                    throw new CodeCannotBeGenerated("Enum {$enum->fqcn} has several values named {$name} with different numbers.");
                }

                continue;
            }

            $memberValues[$name] = $case->value;

            if (isset($caseNames[$case->value])) {
                $member = $enumType
                    ->addConstant($name, new Literal("self::{$caseNames[$case->value]}"))
                    ->setType('self');
            } else {
                $member = $enumType->addCase($name, $case->value);
                $caseNames[$case->value] = $name;
            }

            $member
                ->addComment(($deprecated = $case->options?->deprecated) === true ? '@deprecated' : '')
                ->addComment(($deprecated === true ? "\n" : '') . (string) $case->comment);
        }

        if ($enum->options?->deprecated === true) {
            $enumType = $enumType->addComment('@deprecated');
        }

        if ($enum->comment !== null) {
            $enumType = $enumType->addComment("\n{$enum->comment}");
        }

        $namespace->add($enumType);

        return $namespace;
    }

    /**
     * @return iterable<string, PhpNamespace>
     * @throws CodeCannotBeGenerated
     */
    public function generateMessages(Parser\MessageDescriptor $message): iterable
    {
        $namespace = $this->namespacer->create($message->path);

        $className = Naming::pascalCase($message->name);

        if ($message->fqcn === '') {
            throw new CodeCannotBeGenerated('Message name must not be empty.');
        }

        /** @var class-string $messageFqcn */
        $messageFqcn = "{$namespace->getName()}\\{$className}";

        $this->index->addMessageType(new File\MessageDescriptor(
            $message->fqcn,
            $messageFqcn,
        ));

        $interfaces = self::oneofInterfaceNames($message);

        yield $message->path => $this->generateMessage($namespace, $className, $message, oneofInterfaces: $interfaces);

        $oneofByIndex = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null) {
                if (isset($message->oneofs[$field->oneOfIndex], $interfaces[$field->oneOfIndex])) {
                    $oneOf = $message->oneofs[$field->oneOfIndex];

                    yield from $this->generateOneofVariant(
                        $message,
                        $oneOf,
                        $interfaces[$field->oneOfIndex],
                        $field,
                    );

                    $oneofByIndex[$field->oneOfIndex][] = $field;
                }
            }
        }

        foreach ($message->oneofs as $idx => $oneof) {
            $variants = $oneofByIndex[$idx] ?? [];

            if ($variants !== [] && isset($interfaces[$idx])) {
                yield from $this->generateOneof($message, $oneof, $interfaces[$idx], $variants);
            }
        }
    }

    /**
     * Names of the sealed interfaces generated for the oneofs of $message, keyed by oneof index. The only place
     * the name is decided: the interface itself, the `implements` of its variants and the oneof property all take it from here.
     *
     * An interface is named after its oneof. When a nested message or enum already owns that class name, the
     * interface steps aside to "{Name}Oneof" (then "{Name}Oneof2", ...), the nested type keeps its name.
     * The variant classes are named after the oneof as before, a clash of theirs is reported, not resolved.
     * Synthetic oneofs of proto3 `optional` fields have no interface and take no part in this.
     *
     * @return array<int, string>
     * @throws CodeCannotBeGenerated
     */
    private static function oneofInterfaceNames(Parser\MessageDescriptor $message): array
    {
        // PHP class names are case-insensitive, so all of them are compared lowercased.
        /** @var array<string, string> $owners lowercased class name => what generates it */
        $owners = [];

        foreach ($message->messages as $nested) {
            // Map entries are not generated, see Compiler::doGenerateMessages().
            if ($nested->options?->mapEntry !== true) {
                $owners[strtolower(Naming::pascalCase($nested->name))] ??= "nested message {$nested->name}";
            }
        }

        foreach ($message->enums as $nested) {
            $owners[strtolower(Naming::pascalCase($nested->name))] ??= "nested enum {$nested->name}";
        }

        /** @var array<int, Parser\OneOfDescriptor> $oneofs oneofs with an interface, i.e. with non-synthetic variants */
        $oneofs = [];
        /** @var array<int, list<Parser\FieldDescriptor>> $variants */
        $variants = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null && isset($message->oneofs[$field->oneOfIndex])) {
                $oneofs[$field->oneOfIndex] = $message->oneofs[$field->oneOfIndex];
                $variants[$field->oneOfIndex][] = $field;
            }
        }

        ksort($oneofs);

        /** @var array<string, true> $taken */
        $taken = [];

        foreach ($oneofs as $idx => $oneof) {
            foreach ($variants[$idx] ?? [] as $field) {
                $taken[strtolower(self::oneofVariantClassName($oneof, $field))] = true;
            }
        }

        $names = [];
        $renamed = [];

        foreach ($oneofs as $idx => $oneof) {
            $name = Naming::pascalCase($oneof->name);

            if (isset($owners[strtolower($name)])) {
                $renamed[$idx] = $oneof;
            } else {
                $names[$idx] = $name;
                $taken[strtolower($name)] = true;
                self::claimClassName($owners, $message, $name, "interface of the oneof {$oneof->name}");
            }
        }

        // All variants and the names kept by the other oneofs are reserved above; the renamed oneofs
        // then take free names in the declaration order, so the outcome is fixed by the schema.
        foreach ($renamed as $idx => $oneof) {
            $name = Naming::unique(Naming::pascalCase(Naming::pascalCase($oneof->name) . 'Oneof'), $owners + $taken, ignoreCase: true);

            $names[$idx] = $name;
            $taken[strtolower($name)] = true;
            self::claimClassName($owners, $message, $name, "interface of the oneof {$oneof->name}");
        }

        ksort($names);

        foreach ($oneofs as $idx => $oneof) {
            foreach ($variants[$idx] ?? [] as $field) {
                self::claimClassName(
                    $owners,
                    $message,
                    self::oneofVariantClassName($oneof, $field),
                    "variant {$field->name} of the oneof {$oneof->name}",
                );
            }
        }

        return $names;
    }

    /**
     * @param array<string, string> $owners
     * @throws CodeCannotBeGenerated
     */
    private static function claimClassName(array &$owners, Parser\MessageDescriptor $message, string $className, string $owner): void
    {
        $key = strtolower($className);

        if (isset($owners[$key])) {
            throw new CodeCannotBeGenerated("Message {$message->fqcn}: class {$className} for the {$owner} conflicts with the {$owners[$key]}.");
        }

        $owners[$key] = $owner;
    }

    /**
     * @param list<string> $implements
     * @param array<int, string> $oneofInterfaces names of the oneof interfaces, see oneofInterfaceNames()
     * @throws CodeCannotBeGenerated
     */
    private function generateMessage(
        PhpNamespace $namespace,
        string $className,
        Parser\MessageDescriptor $message,
        array $implements = [],
        array $oneofInterfaces = [],
    ): PhpNamespace {
        $classType = new ClassType($className)
            ->setFinal()
            ->setReadOnly()
            ->setImplements($implements);

        $classType = $classType->addComment('@api');

        if ($message->options?->deprecated === true) {
            $classType = $classType->addComment('@deprecated');
        }

        if ($message->comment !== null) {
            $classType = $classType->addComment("\n{$message->comment}");
        }

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

            $features = $field->features;

            if ($features?->messageEncoding === FeatureSet\MessageEncoding::DELIMITED) {
                throw new CodeCannotBeGenerated('DELIMITED message encoding are not supported');
            }

            $parameterName = $field->jsonName;

            if ($parameterName === null || $parameterName === '') {
                $parameterName = Naming::propertyName($field->name);
            }

            $parameter = $constructor->addPromotedParameter($parameterName);

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
            $required = $field->label === FieldDescriptorProto\Label::LABEL_REQUIRED || $features?->fieldPresence === FeatureSet\FieldPresence::LEGACY_REQUIRED;

            $presence = $this->edition !== null
                ? $features?->fieldPresence === FeatureSet\FieldPresence::EXPLICIT
                : $field->optional;

            $nullable = ($type->nullable || $presence) && !$repeated && !$required && !($field->type === FieldDescriptorProto\Type::TYPE_ENUM && $field->defaultValue !== null);

            $phpType = ($nullable ? '?' : '') . ($repeated ? 'array' : $type->phpType);

            $reflectionType = $type->reflectionType;

            if ($repeated) {
                $parameters = [$reflectionType];

                // In proto2 all repeated fields are non-packed by default unless explicitly marked with [packed = true].
                if ($this->syntax === null) {
                    $parameters[] = $field->options?->packed === true;
                } elseif ($this->edition !== null) {
                    // In editions, PACKED is the default for eligible types unless explicitly set to EXPANDED.
                    $parameters[] = $features?->repeatedFieldEncoding === null || $features->repeatedFieldEncoding === FeatureSet\RepeatedFieldEncoding::PACKED;
                }

                $reflectionType = Literal::new('Reflection\ListT', $parameters);
            }

            $default = $nullable ? null : $type->default;

            // Only bake the proto-defined default into the constructor when the field
            // is non-nullable. For nullable fields we keep `null` so that presence
            // round-trips: the user can distinguish "field unset" from "field set to its proto default".
            if (!$nullable && $field->defaultValue !== null && $field->type !== null) {
                $default = $this->parseDefaultValue(
                    $field->type,
                    $field->defaultValue,
                    $field->typeName,
                );
            }

            $parameter
                ->setType($phpType)
                ->addAttribute('Reflection\Field', [
                    $field->number,
                    $reflectionType,
                ])
                ->setNullable($nullable);

            // A field becomes an optional constructor argument (gets a default) when it
            // is not required, or when the schema declares an explicit default for it —
            // in which case that declared default stands in for the value, as proto2 and
            // editions do. A required field without a declared default stays mandatory:
            // the caller must supply it. proto2 and editions behave identically here.
            $hasDefault = !$required || $field->defaultValue !== null;

            if ($hasDefault) {
                $parameter->setDefaultValue($repeated ? [] : $default);
            }

            if ($field->options?->deprecated === true) {
                $parameter->addComment('@deprecated');
            }

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
            if (!isset($message->oneofs[$idx], $oneofInterfaces[$idx])) {
                continue;
            }

            $oneOf = $message->oneofs[$idx];

            // Variants are named after the oneof itself, even when its interface has been renamed.
            $oneOfName = Naming::pascalCase($oneOf->name);

            $constructor
                ->addPromotedParameter(Naming::propertyName($oneOf->name))
                ->setType($this->namespacer->fqcn("{$message->path}.{$oneofInterfaces[$idx]}"))
                ->setNullable()
                ->setDefaultValue(null)
                ->addAttribute('Reflection\OneOf', [
                    array_map(
                        fn(Parser\FieldDescriptor $variant) => new Literal(
                            \sprintf(
                                '%s::class',
                                $this->namespacer->fqcn(
                                    "{$message->path}." . self::oneofVariantName($oneOfName, $variant),
                                ),
                            ),
                        ),
                        $variants,
                    ),
                ]);
        }

        $this->reorderRequiredFirst($constructor);

        return $namespace;
    }

    private function reorderRequiredFirst(Method $constructor): void
    {
        [$required, $optional] = [[], []];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->hasDefaultValue()) {
                $optional[] = $parameter;
            } else {
                $required[] = $parameter;
            }
        }

        if ($required === [] || $optional === []) {
            return;
        }

        $constructor->setParameters([
            ...$required,
            ...$optional,
        ]);
    }

    /**
     * @param list<Parser\FieldDescriptor> $variants
     * @return iterable<string, PhpNamespace>
     */
    private function generateOneof(
        Parser\MessageDescriptor $message,
        Parser\OneOfDescriptor $oneof,
        string $interfaceName,
        array $variants,
    ): iterable {
        $interfaceType = new InterfaceType($interfaceName)
            ->addComment('@api')
            ->addComment('@phpstan-sealed (')
            ->addComment(implode(" |\n", array_map(
                static fn(Parser\FieldDescriptor $variant) => \sprintf('  %s', self::oneofVariantName($oneof->name, $variant)),
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
        string $interfaceName,
        Parser\FieldDescriptor $variant,
    ): iterable {
        $className = self::oneofVariantName($oneof->name, $variant);

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
                    jsonName: $variant->jsonName,
                ),
            ],
        );

        yield $path => $this->generateMessage(
            $this->namespacer->create($path),
            Naming::pascalCase($className),
            $descriptor,
            [
                $this->namespacer->fqcn("{$message->path}.{$interfaceName}"),
            ],
        );
    }

    private function parseDefaultValue(
        FieldDescriptorProto\Type $type,
        string $defaultValue,
        ?string $typename = null,
    ): mixed {
        if ($type === FieldDescriptorProto\Type::TYPE_ENUM && $typename !== null) {
            // Resolved through the graph, so that a relocated enum is referenced
            // under the very namespace it is generated into.
            return new Literal(\sprintf("%s::{$defaultValue}", $this->graph->get($typename)->fqcn));
        }

        return match ($type) {
            FieldDescriptorProto\Type::TYPE_STRING,
            FieldDescriptorProto\Type::TYPE_BYTES => $defaultValue,
            FieldDescriptorProto\Type::TYPE_INT32,
            FieldDescriptorProto\Type::TYPE_SINT32,
            FieldDescriptorProto\Type::TYPE_UINT32,
            FieldDescriptorProto\Type::TYPE_FIXED32,
            FieldDescriptorProto\Type::TYPE_SFIXED32,
            FieldDescriptorProto\Type::TYPE_INT64,
            FieldDescriptorProto\Type::TYPE_SINT64,
            FieldDescriptorProto\Type::TYPE_SFIXED64 => filter_var($defaultValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0,
            FieldDescriptorProto\Type::TYPE_UINT64,
            FieldDescriptorProto\Type::TYPE_FIXED64 => Literal::new('\BcMath\Number', [
                filter_var($defaultValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0,
            ]),
            FieldDescriptorProto\Type::TYPE_BOOL => filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN),
            FieldDescriptorProto\Type::TYPE_FLOAT,
            FieldDescriptorProto\Type::TYPE_DOUBLE => filter_var($defaultValue, FILTER_VALIDATE_FLOAT),
            default => null,
        };
    }

    private static function oneofVariantName(string $oneof, Parser\FieldDescriptor $variant): string
    {
        if ($variant->jsonName !== null) {
            $name = \sprintf('%s%s', Naming::pascalCase($oneof), ucfirst($variant->jsonName));
        } else {
            $name = Naming::pascalCase("{$oneof} {$variant->name}");
        }

        return Naming::secure($name);
    }

    /**
     * The name the variant class is declared with, see generateOneofVariant().
     */
    private static function oneofVariantClassName(Parser\OneOfDescriptor $oneof, Parser\FieldDescriptor $variant): string
    {
        return Naming::pascalCase(self::oneofVariantName($oneof->name, $variant));
    }
}
