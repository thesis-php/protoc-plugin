<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Amp\Cancellation;
use Amp\NullCancellation;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use Nette\PhpGenerator\PsrPrinter;
use Thesis\Grpc\Client;
use Thesis\Grpc\Metadata;
use Thesis\Grpc\Server;
use Thesis\Grpc\Server\ServiceRegistry;
use Thesis\Protobuf\Compiler\FieldDescriptorProto;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Generator
{
    private PsrPrinter $printer;

    private ProtoTypeDeclarationResolver $protoTypes;

    private GrpcTypeDeclarationResolver $grpcTypes;

    /**
     * @param array<string, FileDescriptor> $protos
     */
    public function __construct(
        private string $namespace,
        private string $path,
        private string $pluginVersion,
        private string $protocVersion,
        private string $source,
        private ?string $package = null,
        private ?string $syntax = null,
        array $protos = [],
    ) {
        $this->printer = new Printer();
        $this->protoTypes = new Type\AggregateTypeDeclarationResolver(protoResolvers: [
            new Type\KnownTypeDeclarationResolver(),
            new Type\NativeProtoTypeDeclarationResolver(),
            new Type\UserProtoTypeDeclarationResolver(),
        ]);
        $this->grpcTypes = new Type\AggregateTypeDeclarationResolver(grpcResolvers: [
            new Type\KnownTypeDeclarationResolver(),
            new Type\RelativeNameGrpcTypeDeclarationResolver(array_values($protos)),
        ]);
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateMessages(MessageDescriptor $message): iterable
    {
        yield $this->generateMessage($message);

        $oneofByIndex = [];

        foreach ($message->fields as $field) {
            if ($field->oneOfIndex !== null && $field->proto3Optional === null) {
                if (isset($message->oneofs[$field->oneOfIndex])) {
                    $oneOf = $message->oneofs[$field->oneOfIndex];

                    yield $this->generateOneofVariant(
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
                yield $this->generateOneof($message, $oneof, $variants);
            }
        }
    }

    public function generateEnum(EnumDescriptor $enum): CodeGeneratorResponse\File
    {
        $enumType = new EnumType(Naming::pascalCase($enum->name))
            ->addComment('@api')
            ->addComment($enum->comment !== null ? "\n{$enum->comment}" : '')
            ->setType('int')
            ->setCases(array_map(
                static fn(EnumCaseDescriptor $case) => new EnumCase(Naming::secureEnumCase($case->name))
                    ->setValue($case->value)
                    ->setComment((string) $case->comment),
                $enum->cases,
            ));

        return $this->createFile($this->createPhpNamespace($enum->path)->add($enumType), $enum->path);
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateGrpcClient(ServiceDescriptor $service): iterable
    {
        $this->doGenerateGrpcClient($namespace = $this->createPhpNamespace($service->path), $service);

        yield $this->createFile($namespace, "{$service->path}Client");
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateGrpcServer(ServiceDescriptor $service): iterable
    {
        $this->doGenerateGrpcServer($namespace = $this->createPhpNamespace($service->path), $service);

        yield $this->createFile($namespace, "{$service->path}Server");

        if ($service->methods !== []) {
            $this->doGenerateGrpcServerRegistry($namespace = $this->createPhpNamespace($service->path), $service);

            yield $this->createFile($namespace, "{$service->path}ServerRegistry");
        }
    }

    /**
     * @param list<string> $implements
     */
    private function generateMessage(MessageDescriptor $message, array $implements = []): CodeGeneratorResponse\File
    {
        $className = Naming::pascalCase($message->name);

        $classType = new ClassType($className)
            ->setFinal()
            ->setReadOnly()
            ->setImplements($implements)
            ->addComment('@api')
            ->addComment($message->comment !== null ? "\n{$message->comment}" : '');

        $namespace = $this->createPhpNamespace($message->path);

        $namespace->add($classType);

        // To avoid creating an empty constructor, we should return here.
        if ($message->fields === []) {
            return $this->createFile(
                $namespace,
                $message->path,
            );
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

            $type = $this->findMapType($message, $field, $namespace) ?? $this->protoTypes->resolveProtoType($field, $namespace);

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
                ->addAttribute(Reflection\Field::class, [
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
                    $this->namespace,
                    $message->path,
                    $oneOfName,
                ]))
                ->setNullable()
                ->setDefaultValue(null)
                ->addAttribute(Reflection\OneOf::class, [
                    array_map(
                        static fn(FieldDescriptor $variant) => new Literal(
                            \sprintf("{$className}\\{$oneOfName}%s::class", Naming::pascalCase($variant->name)),
                        ),
                        $variants,
                    ),
                ]);
        }

        return $this->createFile(
            $namespace,
            $message->path,
        );
    }

    /**
     * @param list<FieldDescriptor> $variants
     */
    private function generateOneof(
        MessageDescriptor $message,
        OneOfDescriptor $oneof,
        array $variants,
    ): CodeGeneratorResponse\File {
        $interfaceName = Naming::pascalCase($oneof->name);

        $interfaceType = new InterfaceType($interfaceName)
            ->addComment('@api')
            ->addComment('@phpstan-sealed (')
            ->addComment(implode(" |\n", array_map(
                static fn(FieldDescriptor $variant) => \sprintf('  %s%s', $interfaceName, Naming::pascalCase($variant->name)),
                $variants,
            )))
            ->addComment(')');

        $path = "{$message->path}.{$interfaceType->getName()}";

        $namespace = $this->createPhpNamespace($path);

        $namespace->add($interfaceType);

        return $this->createFile(
            $namespace,
            $path,
        );
    }

    private function generateOneofVariant(
        MessageDescriptor $message,
        OneOfDescriptor $oneof,
        FieldDescriptor $variant,
    ): CodeGeneratorResponse\File {
        $interfaceName = Naming::pascalCase($oneof->name);
        $className = \sprintf('%s%s', $interfaceName, Naming::pascalCase($variant->name));

        $descriptor = new MessageDescriptor(
            name: $className,
            path: "{$message->path}.{$className}",
            fields: [
                // Remove oneof index.
                new FieldDescriptor(
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

        return $this->generateMessage($descriptor, [
            Naming::joinNamespace([
                $this->namespace,
                $message->path,
                $interfaceName,
            ]),
        ]);
    }

    public function doGenerateGrpcClient(PhpNamespace $namespace, ServiceDescriptor $service): void
    {
        $name = \sprintf('%sClient', Naming::pascalCase($service->name));

        $classType = new ClassType($name)
            ->setFinal()
            ->setReadOnly()
            ->addComment('@api')
            ->addComment($service->comment !== null ? "\n{$service->comment}" : '');

        $namespace->add($classType);

        if ($service->methods === []) {
            return;
        }

        $namespace->addUse(Cancellation::class);
        $namespace->addUse(NullCancellation::class);
        $namespace->addUse(Client::class);
        $namespace->addUse(Metadata::class);

        $constructor = $classType->addMethod('__construct');
        $constructor
            ->addPromotedParameter('client')
            ->setPrivate()
            ->setType(Client::class);

        foreach ($service->methods as $method) {
            $classMethod = $classType
                ->addMethod(Naming::camelCase($method->name))
                ->setPublic();

            if ($method->comment !== null) {
                $classMethod->addComment((string) $method->comment);
            }

            $parameters = [
                new Parameter('md')
                    ->setType(Metadata::class)
                    ->setDefaultValue(Literal::new('Metadata')),
                new Parameter('cancellation')
                    ->setType(Cancellation::class)
                    ->setDefaultValue(Literal::new('NullCancellation')),
            ];

            $in = $this->grpcTypes->resolveGrpcType($method->inType);
            $out = $this->grpcTypes->resolveGrpcType($method->outType);

            $namespace->addUse($in->use);
            $namespace->addUse($out->use);

            if (!$method->clientStreaming && !$method->bidirectionalStreaming) {
                $parameters = [
                    new Parameter('request')->setType($in->use),
                    ...$parameters,
                ];
            }

            $classMethod->setParameters($parameters);

            $methodBodyParameters = [
                new Literal($in->name),
                new Literal($out->name),
                '/' . ($this->package !== null ? "{$this->package}.{$service->name}" : $service->name) . "/{$method->name}",
                new Literal($out->name),
            ];

            $phpdocPrefix = ($method->comment !== null ? "\n" : '');

            if (!$method->clientStreaming && !$method->serverStreaming) {
                $classMethod
                    ->setBody(
                        <<<'PHP'
/** @var Client\Invoke<?, ?> $invoke */
$invoke = new Client\Invoke(
    method: ?,
    type: ?::class,
);

return $this->client->invoke(
    request: $request,
    invoke: $invoke,
    md: $md,
    cancellation: $cancellation,
);
PHP,
                        $methodBodyParameters,
                    )
                    ->setReturnType($out->use);
            } elseif ($method->clientStreaming && !$method->serverStreaming) {
                $classMethod
                    ->setBody(
                        <<<'PHP'
/** @var Client\Invoke<?, ?> $invoke */
$invoke = new Client\Invoke(
    method: ?,
    type: ?::class,
);

$stream = $this->client->createStream(
    invoke: $invoke,
    md: $md,
    cancellation: $cancellation,
);

return new Client\ClientStreamChannel($stream);
PHP,
                        $methodBodyParameters,
                    )
                    ->setReturnType(Client\ClientStreamChannel::class)
                    ->addComment("{$phpdocPrefix}@return Client\\ClientStreamChannel<{$in->name}, {$out->name}>");
            } elseif (!$method->clientStreaming && $method->serverStreaming) {
                $classMethod
                    ->setBody(
                        <<<'PHP'
/** @var Client\Invoke<?, ?> $invoke */
$invoke = new Client\Invoke(
    method: ?,
    type: ?::class,
);

$stream = $this->client->createStream(
    invoke: $invoke,
    md: $md,
    cancellation: $cancellation,
);

$stream->send($request);
$stream->close();

return new Client\ServerStreamChannel($stream);
PHP,
                        $methodBodyParameters,
                    )
                    ->setReturnType(Client\ServerStreamChannel::class)
                    ->addComment("{$phpdocPrefix}@return Client\\ServerStreamChannel<{$in->name}, {$out->name}>");
            } else {
                $classMethod
                    ->setBody(
                        <<<'PHP'
/** @var Client\Invoke<?, ?> $invoke */
$invoke = new Client\Invoke(
    method: ?,
    type: ?::class,
);

$stream = $this->client->createStream(
    invoke: $invoke,
    md: $md,
    cancellation: $cancellation,
);

return new Client\BidirectionalStreamChannel($stream);
PHP,
                        $methodBodyParameters,
                    )
                    ->setReturnType(Client\BidirectionalStreamChannel::class)
                    ->addComment("{$phpdocPrefix}@return Client\\BidirectionalStreamChannel<{$in->name}, {$out->name}>");
            }
        }
    }

    private function doGenerateGrpcServer(PhpNamespace $namespace, ServiceDescriptor $service): void
    {
        $interfaceType = new InterfaceType(\sprintf('%sServer', Naming::pascalCase($service->name)))
            ->addComment('@api')
            ->addComment($service->comment !== null ? "\n{$service->comment}" : '');

        $namespace->add($interfaceType);

        if ($service->methods === []) {
            return;
        }

        $namespace->addUse(Cancellation::class);
        $namespace->addUse(Metadata::class);

        $useStreaming = array_any(
            $service->methods,
            static fn(ServiceMethodDescriptor $method) => $method->clientStreaming || $method->serverStreaming,
        );

        if ($useStreaming) {
            $namespace->addUse(Server::class);
        }

        foreach ($service->methods as $method) {
            $interfaceMethod = $interfaceType
                ->addMethod(Naming::camelCase($method->name))
                ->setPublic();

            if ($method->comment !== null) {
                $interfaceMethod->addComment((string) $method->comment);
            }

            $phpdocPrefix = ($method->comment !== null ? "\n" : '');

            $in = $this->grpcTypes->resolveGrpcType($method->inType);
            $out = $this->grpcTypes->resolveGrpcType($method->outType);

            $namespace->addUse($in->use);
            $namespace->addUse($out->use);

            if (!$method->clientStreaming && !$method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('request')->setType($in->use),
                        new Parameter('md')->setType(Metadata::class),
                        new Parameter('cancellation')->setType(Cancellation::class),
                    ])
                    ->setReturnType($out->use);
            } elseif ($method->clientStreaming && !$method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('stream')->setType(Server\ClientStreamChannel::class),
                        new Parameter('md')->setType(Metadata::class),
                        new Parameter('cancellation')->setType(Cancellation::class),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\ClientStreamChannel<{$in->name}, {$out->name}> \$stream");
            } elseif (!$method->clientStreaming && $method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('request')->setType($in->use),
                        new Parameter('stream')->setType(Server\ServerStreamChannel::class),
                        new Parameter('md')->setType(Metadata::class),
                        new Parameter('cancellation')->setType(Cancellation::class),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\ServerStreamChannel<{$in->name}, {$out->name}> \$stream");
            } else {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('stream')->setType(Server\BidirectionalStreamChannel::class),
                        new Parameter('md')->setType(Metadata::class),
                        new Parameter('cancellation')->setType(Cancellation::class),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\BidirectionalStreamChannel<{$in->name}, {$out->name}> \$stream");
            }
        }
    }

    public function doGenerateGrpcServerRegistry(PhpNamespace $namespace, ServiceDescriptor $service): void
    {
        $classType = new ClassType(\sprintf('%sServerRegistry', Naming::pascalCase($service->name)))
            ->addComment('@api')
            ->setFinal()
            ->setReadOnly()
            ->setImplements([
                ServiceRegistry::class,
            ]);

        $namespace->add($classType);

        $namespace->addUse(Server::class);

        $handlers = [];

        foreach ($service->methods as $method) {
            $in = $this->grpcTypes->resolveGrpcType($method->inType);

            $methodName = Naming::camelCase($method->name);

            $args = [
                $method->name,
                new Literal($in->name),
                $methodName,
            ];

            $namespace->addUse($in->use);

            if (!$method->clientStreaming && !$method->serverStreaming) {
                $handlers[] = new Literal(
                    <<<'PHP'
    new Server\Rpc(
        new Server\Handle(?, ?::class),
        new Server\UnaryHandler($this->server->?(...)),
    )
PHP,
                    $args,
                );
            } elseif ($method->clientStreaming && !$method->serverStreaming) {
                $handlers[] = new Literal(
                    <<<'PHP'
    new Server\Rpc(
        new Server\Handle(?, ?::class),
        new Server\ClientStreamHandler($this->server->?(...)),
    )
PHP,
                    $args,
                );
            } elseif (!$method->clientStreaming && $method->serverStreaming) {
                $handlers[] = new Literal(
                    <<<'PHP'
    new Server\Rpc(
        new Server\Handle(?, ?::class),
        new Server\ServerStreamHandler($this->server->?(...)),
    )
PHP,
                    $args,
                );
            } else {
                $handlers[] = new Literal(
                    <<<'PHP'
    new Server\Rpc(
        new Server\Handle(?, ?::class),
        new Server\BidirectionalStreamHandler($this->server->?(...)),
    )
PHP,
                    $args,
                );
            }
        }

        $classType
            ->addMethod('__construct')
            ->setParameters([
                new PromotedParameter('server')
                    ->setType($namespace->getName() . '\\' . \sprintf('%sServer', Naming::pascalCase($service->name)))
                    ->setPrivate(),
            ]);

        $classType
            ->addMethod('services')
            ->setPublic()
            ->setReturnType('iterable')
            ->addAttribute(\Override::class)
            ->setBody(
                \sprintf(
                    <<<'PHP'
yield new Server\Service(?, [
    %s,
]);
PHP,
                    implode(",\n\t", array_fill(0, \count($handlers), '?')),
                ),
                [
                    $this->package !== null ? "{$this->package}.{$service->name}" : $service->name,
                    ...$handlers,
                ],
            );
    }

    private function createFile(
        PhpNamespace $namespace,
        string $path,
    ): CodeGeneratorResponse\File {
        $generatedDoc = \sprintf(
            <<<'DOC'
Code generated by thesis/protoc-plugin. DO NOT EDIT.
Versions:
  thesis/protoc-plugin — v%s
  protoc               — v%s
Source: %s
DOC,
            $this->pluginVersion,
            $this->protocVersion,
            $this->source,
        );

        $file = new PhpFile()
            ->setStrictTypes()
            ->setComment($generatedDoc)
            ->add($namespace);

        return new CodeGeneratorResponse\File(
            name: \sprintf('%s/%s.php', $this->path, Naming::path($path)),
            content: $this->printer->printFile($file),
        );
    }

    private function createPhpNamespace(string $path): PhpNamespace
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
            FieldDescriptorProto\Type::TYPE_SFIXED64 => Literal::new('Number', [
                filter_var($defaultValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0,
            ]),
            FieldDescriptorProto\Type::TYPE_BOOL => filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN),
            FieldDescriptorProto\Type::TYPE_FLOAT,
            FieldDescriptorProto\Type::TYPE_DOUBLE => filter_var($defaultValue, FILTER_VALIDATE_FLOAT),
            default => null,
        };
    }

    private function findMapType(
        MessageDescriptor $message,
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ?ProtoTypeDeclaration {
        $isMapType = fn(MessageDescriptor $messageType) => $messageType->options?->mapEntry === true && $field->typeName === ".{$this->package}.{$messageType->path}";

        foreach ($message->messages as $messageType) {
            if (!$isMapType($messageType)) {
                continue;
            }

            if (\count($messageType->fields) !== 2) {
                throw new \LogicException('MapEntry must have 2 fields.');
            }

            $keyType = $this->protoTypes->resolveProtoType(
                $messageType->fields[0],
                $namespace,
            );

            $valueType = $this->protoTypes->resolveProtoType(
                $messageType->fields[1],
                $namespace,
            );

            return new ProtoTypeDeclaration(
                phpType: Map::class,
                reflectionType: Literal::new('Reflection\MapT', [
                    $keyType->reflectionType,
                    $valueType->reflectionType,
                ]),
                uses: [
                    'Thesis\Protobuf',
                    ...$keyType->uses,
                    ...$valueType->uses,
                ],
                nullable: false,
                default: Literal::new('Protobuf\Map'),
                docType: "Protobuf\\Map<{$keyType->resolvedType()}, {$valueType->resolvedType()}>",
                isMap: true,
            );
        }

        return null;
    }
}
