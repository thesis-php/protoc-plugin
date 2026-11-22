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

        $runtime = new RuntimeImports($namespace);

        $constructor = $classType->addMethod('__construct');

        $oneOfByIndex = [];

        /** @var array<string, string> $properties property name => what generates it */
        $properties = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null) {
                $oneOfByIndex[$field->oneOfIndex][] = $field;

                continue;
            }

            $features = $field->features;

            if ($features?->messageEncoding === FeatureSet\MessageEncoding::DELIMITED) {
                throw new CodeCannotBeGenerated('DELIMITED message encoding are not supported');
            }

            $parameterName = self::fieldPropertyName($field);

            self::claimPropertyName($properties, $message, $parameterName, "field {$field->name}");

            $parameter = $constructor->addPromotedParameter($parameterName);

            $type = null;

            if ($field->map !== null) {
                $keyType = $this->types->create($field->map->key, $runtime);
                $valueType = $this->types->create($field->map->value, $runtime);
                $map = $runtime->protobuf('Map');

                $type = new TypeDeclaration(
                    phpType: $map,
                    reflectionType: Literal::new($runtime->reflection('MapT'), [$keyType->reflectionType, $valueType->reflectionType]),
                    nullable: false,
                    isMap: true,
                    docType: "{$map}<{$keyType->resolvedType()}, {$valueType->resolvedType()}>",
                    default: Literal::new($map),
                );
            }

            $type ??= $this->types->create($field, $runtime);

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
                    // The feature resolves to PACKED for any field, but only packable types are packed.
                    $parameters[] = $type->packable && ($features?->repeatedFieldEncoding === null || $features->repeatedFieldEncoding === FeatureSet\RepeatedFieldEncoding::PACKED);
                } elseif ($field->options?->packed === false) {
                    // In proto3, packable fields are packed by default, which is also what the runtime does
                    // when packed is not given; only an explicit [packed = false] needs to be spelled out.
                    $parameters[] = false;
                }

                $reflectionType = Literal::new($runtime->reflection('ListT'), $parameters);
            }

            $default = $nullable ? null : $type->default;

            // Only bake the proto-defined default into the constructor when the field
            // is non-nullable. For nullable fields we keep `null` so that presence
            // round-trips: the user can distinguish "field unset" from "field set to its proto default".
            if (!$nullable && $field->defaultValue !== null && $field->type !== null) {
                $default = $this->parseDefaultValue($message, $field, $field->type, $field->defaultValue);
            }

            $parameter
                ->setType($phpType)
                ->addAttribute($runtime->reflection('Field'), [
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

            $propertyName = self::oneofPropertyName($oneOf);

            self::claimPropertyName($properties, $message, $propertyName, "oneof {$oneOf->name}");

            $constructor
                ->addPromotedParameter($propertyName)
                ->setType($this->namespacer->fqcn("{$message->path}.{$oneofInterfaces[$idx]}"))
                ->setNullable()
                ->setDefaultValue(null)
                ->addAttribute($runtime->reflection('OneOf'), [
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

    /**
     * Two fields or oneofs of one message must not end up with the same property, which would silently drop one of them.
     * protoc rejects only clashing JSON names, and in proto2 merely warns about them, while a property name
     * also comes from a oneof name or from the fallback of fieldBaseName(). PHP variable names are case-sensitive.
     *
     * @param array<string, string> $properties
     * @throws CodeCannotBeGenerated
     */
    private static function claimPropertyName(array &$properties, Parser\MessageDescriptor $message, string $name, string $owner): void
    {
        if (isset($properties[$name])) {
            throw new CodeCannotBeGenerated("Message {$message->fqcn}: the {$properties[$name]} and the {$owner} both become the property \${$name}.");
        }

        $properties[$name] = $owner;
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

    /**
     * Turns FieldDescriptorProto.default_value into the PHP default of the constructor parameter. The value comes
     * as text: numbers as written in the schema (floats may be inf, -inf, nan), bools as true/false, strings as is,
     * bytes C-escaped, enums as the name of the value.
     *
     * @throws CodeCannotBeGenerated
     */
    private function parseDefaultValue(
        Parser\MessageDescriptor $message,
        Parser\FieldDescriptor $field,
        FieldDescriptorProto\Type $type,
        string $defaultValue,
    ): mixed {
        $value = match ($type) {
            FieldDescriptorProto\Type::TYPE_ENUM => $this->enumDefault($field, $defaultValue),
            FieldDescriptorProto\Type::TYPE_STRING => $defaultValue,
            FieldDescriptorProto\Type::TYPE_BYTES => self::unescapeBytes($defaultValue),
            FieldDescriptorProto\Type::TYPE_INT32,
            FieldDescriptorProto\Type::TYPE_SINT32,
            FieldDescriptorProto\Type::TYPE_SFIXED32 => self::integerDefault($defaultValue, -2_147_483_648, 2_147_483_647),
            FieldDescriptorProto\Type::TYPE_UINT32,
            FieldDescriptorProto\Type::TYPE_FIXED32 => self::integerDefault($defaultValue, 0, 4_294_967_295),
            // var_export(), and so Nette, prints PHP_INT_MIN as -9223372036854775807-1: the literal
            // -9223372036854775808 would be a float.
            FieldDescriptorProto\Type::TYPE_INT64,
            FieldDescriptorProto\Type::TYPE_SINT64,
            FieldDescriptorProto\Type::TYPE_SFIXED64 => self::integerDefault($defaultValue, PHP_INT_MIN, PHP_INT_MAX),
            FieldDescriptorProto\Type::TYPE_UINT64,
            FieldDescriptorProto\Type::TYPE_FIXED64 => self::unsignedInteger64Default($defaultValue),
            FieldDescriptorProto\Type::TYPE_BOOL => match ($defaultValue) {
                'true' => true,
                'false' => false,
                default => null,
            },
            FieldDescriptorProto\Type::TYPE_FLOAT => self::floatDefault($defaultValue, single: true),
            FieldDescriptorProto\Type::TYPE_DOUBLE => self::floatDefault($defaultValue, single: false),
            default => null,
        };

        return $value ?? throw new CodeCannotBeGenerated(\sprintf(
            'Message %s: field %s has the default value "%s" that is not valid for %s.',
            $message->fqcn,
            $field->name,
            addcslashes($defaultValue, "\0..\37\"\\\177..\377"),
            $type->name,
        ));
    }

    private function enumDefault(Parser\FieldDescriptor $field, string $defaultValue): ?Literal
    {
        if ($field->typeName === null) {
            return null;
        }

        // Resolved through the graph, so that a relocated enum is referenced
        // under the very namespace it is generated into.
        $enum = $this->graph->get($field->typeName);

        if (!$enum->container instanceof Parser\EnumDescriptor) {
            return null;
        }

        foreach ($enum->container->cases as $case) {
            // Every value has a member of the generated enum under its secured name: a case, a constant of an alias,
            // or, for a value dropped by generateEnum() as a duplicate of that name, the member it duplicates.
            if ($case->name === $defaultValue) {
                return new Literal(\sprintf('%s::%s', $enum->fqcn, Naming::secureEnumCase($case->name)));
            }
        }

        return null;
    }

    private static function integerDefault(string $value, int $min, int $max): ?int
    {
        if (preg_match('/^-?(?:0|[1-9][0-9]*)$/D', $value) !== 1) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return \is_int($integer) ? $integer : null;
    }

    /**
     * new \BcMath\Number(int) for values that fit PHP int, so that the output of the usual ones is unchanged,
     * and new \BcMath\Number(string) beyond PHP_INT_MAX.
     */
    private static function unsignedInteger64Default(string $value): ?Literal
    {
        if (preg_match('/^(?:0|[1-9][0-9]*)$/D', $value) !== 1) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (\is_int($integer)) {
            return Literal::new('\BcMath\Number', [$integer]);
        }

        // Without leading zeros, a longer number is a greater one.
        $max = '18446744073709551615';

        if (\strlen($value) > \strlen($max) || (\strlen($value) === \strlen($max) && strcmp($value, $max) > 0)) {
            return null;
        }

        return Literal::new('\BcMath\Number', [$value]);
    }

    /**
     * @param bool $single whether the field is a 32-bit float: protoc gives its default with the precision of float,
     *                     the value is rounded to float, so that the PHP default is the one the field holds on the wire
     */
    private static function floatDefault(string $value, bool $single): null|float|Literal
    {
        $special = match (strtolower($value)) {
            'inf', '+inf', 'infinity', '+infinity' => '\INF',
            '-inf', '-infinity' => '-\INF',
            'nan', '+nan', '-nan' => '\NAN',
            default => null,
        };

        if ($special !== null) {
            return new Literal($special);
        }

        if (preg_match('/^[+-]?(?:[0-9]+\.?[0-9]*|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', $value) !== 1) {
            return null;
        }

        $float = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($single && \is_float($float)) {
            $float = unpack('g', pack('g', $float))[1] ?? null;
        }

        return \is_float($float) && is_finite($float) ? $float : null;
    }

    /**
     * Decodes the C-escaped value protoc gives for bytes: octal \NNN (every byte >= 0x80 and other
     * non-printable ones), \n, \r, \t, \", \', \\; also the rest of the C escapes and hex \xHH.
     */
    private static function unescapeBytes(string $value): ?string
    {
        $simple = ['n' => "\n", 'r' => "\r", 't' => "\t", 'a' => "\x07", 'b' => "\x08", 'f' => "\f", 'v' => "\v", '\\' => '\\', "'" => "'", '"' => '"', '?' => '?'];

        $valid = true;

        $bytes = preg_replace_callback(
            '/\\\(?:([0-7]{1,3})|[xX]([0-9a-fA-F]{1,2})|(.)|$)/sD',
            static function (array $matches) use ($simple, &$valid): string {
                if ($matches[1] !== null && ($code = octdec($matches[1])) <= 0xFF) {
                    return \chr((int) $code);
                }

                if ($matches[2] !== null) {
                    return \chr((int) hexdec($matches[2]));
                }

                if ($matches[3] !== null && isset($simple[$matches[3]])) {
                    return $simple[$matches[3]];
                }

                $valid = false;

                return $matches[0];
            },
            $value,
            flags: PREG_UNMATCHED_AS_NULL,
        );

        return $valid ? $bytes : null;
    }

    /**
     * The name the property of $field and the class of a oneof variant holding $field are built from. It is the
     * json_name of the field while that is a valid PHP variable name, so a custom json_name names the property.
     * A json_name that is not ("my-field", "@type", "1st") gives way to the field name in camelCase, which for
     * an ordinary field name is what protoc puts into json_name by default. protoc always fills json_name,
     * the fallback also covers descriptors without it.
     */
    private static function fieldBaseName(Parser\FieldDescriptor $field): string
    {
        if ($field->jsonName !== null && Naming::isVariableName($field->jsonName)) {
            return $field->jsonName;
        }

        return Naming::propertyName($field->name);
    }

    /**
     * The name of the property, i.e. of the promoted constructor parameter, generated for $field, see fieldBaseName().
     * The only place it is decided: the parameter, its `@param` tag and the property of a oneof variant class take it from here.
     */
    private static function fieldPropertyName(Parser\FieldDescriptor $field): string
    {
        return self::variableName(self::fieldBaseName($field), $field->name);
    }

    /**
     * The name of the property generated for $oneof.
     */
    private static function oneofPropertyName(Parser\OneOfDescriptor $oneof): string
    {
        return self::variableName(Naming::propertyName($oneof->name), $oneof->name);
    }

    /**
     * $name with `this` escaped. Only a proto name like `_1st` turns into a camelCase that is not a variable name ("1st"):
     * then the proto name itself is used, a proto identifier always being a valid PHP one.
     */
    private static function variableName(string $name, string $protoName): string
    {
        return Naming::secureVariable(Naming::isVariableName($name) ? $name : $protoName);
    }

    private static function oneofVariantName(string $oneof, Parser\FieldDescriptor $variant): string
    {
        return Naming::secure(\sprintf('%s%s', Naming::pascalCase($oneof), ucfirst(self::fieldBaseName($variant))));
    }

    /**
     * The name the variant class is declared with, see generateOneofVariant().
     */
    private static function oneofVariantClassName(Parser\OneOfDescriptor $oneof, Parser\FieldDescriptor $variant): string
    {
        return Naming::pascalCase(self::oneofVariantName($oneof->name, $variant));
    }
}
