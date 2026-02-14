<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use Thesis\Protoc\Plugin\Dependency;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class GrpcGenerator
{
    public function __construct(
        private PhpNamespacer $namespacer,
        private Dependency\Graph $graph,
        private ?string $package = null,
    ) {}

    public function generateClient(Parser\ServiceDescriptor $service): PhpNamespace
    {
        $namespace = $this->namespacer->create($service->path);

        $name = \sprintf('%sClient', Naming::pascalCase($service->name));

        $classType = new ClassType($name)
            ->setFinal()
            ->setReadOnly()
            ->addComment('@api')
            ->addComment($service->comment !== null ? "\n{$service->comment}" : '');

        $namespace->add($classType);

        if ($service->methods === []) {
            return $namespace;
        }

        $namespace->addUse('Amp\Cancellation');
        $namespace->addUse('Amp\NullCancellation');
        $namespace->addUse('Thesis\Grpc\Client');
        $namespace->addUse('Thesis\Grpc\Metadata');

        $constructor = $classType->addMethod('__construct');
        $constructor
            ->addPromotedParameter('client')
            ->setPrivate()
            ->setType('Client');

        foreach ($service->methods as $method) {
            $classMethod = $classType
                ->addMethod(Naming::camelCase($method->name))
                ->setPublic();

            if ($method->comment !== null) {
                $classMethod->addComment((string) $method->comment);
            }

            $parameters = [
                new Parameter('md')
                    ->setType('Metadata')
                    ->setDefaultValue(Literal::new('Metadata')),
                new Parameter('cancellation')
                    ->setType('Cancellation')
                    ->setDefaultValue(Literal::new('NullCancellation')),
            ];

            $in = $this->graph->get($method->inType);
            $out = $this->graph->get($method->outType);

            if (!$method->clientStreaming && !$method->bidirectionalStreaming) {
                $parameters = [
                    new Parameter('request')->setType($in->fqcn),
                    ...$parameters,
                ];
            }

            $classMethod->setParameters($parameters);

            $methodBodyParameters = [
                new Literal($in->fqcn),
                new Literal($out->fqcn),
                '/' . ($this->package !== null ? "{$this->package}.{$service->name}" : $service->name) . "/{$method->name}",
                new Literal($out->fqcn),
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
                    ->setReturnType($out->fqcn);
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
                    ->setReturnType('Client\ClientStreamChannel')
                    ->addComment("{$phpdocPrefix}@return Client\\ClientStreamChannel<{$in->fqcn}, {$out->fqcn}>");
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
                    ->setReturnType('Client\ServerStreamChannel')
                    ->addComment("{$phpdocPrefix}@return Client\\ServerStreamChannel<{$in->fqcn}, {$out->fqcn}>");
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
                    ->setReturnType('Client\BidirectionalStreamChannel')
                    ->addComment("{$phpdocPrefix}@return Client\\BidirectionalStreamChannel<{$in->fqcn}, {$out->fqcn}>");
            }
        }

        return $namespace;
    }

    public function generateServer(Parser\ServiceDescriptor $service): PhpNamespace
    {
        $namespace = $this->namespacer->create($service->path);

        $interfaceType = new InterfaceType(\sprintf('%sServer', Naming::pascalCase($service->name)))
            ->addComment('@api')
            ->addComment($service->comment !== null ? "\n{$service->comment}" : '');

        $namespace->add($interfaceType);

        if ($service->methods === []) {
            return $namespace;
        }

        $namespace->addUse('Amp\Cancellation');
        $namespace->addUse('Thesis\Grpc\Metadata');

        $useStreaming = array_any(
            $service->methods,
            static fn(Parser\ServiceMethodDescriptor $method) => $method->clientStreaming || $method->serverStreaming,
        );

        if ($useStreaming) {
            $namespace->addUse('Thesis\Grpc\Server');
        }

        foreach ($service->methods as $method) {
            $interfaceMethod = $interfaceType
                ->addMethod(Naming::camelCase($method->name))
                ->setPublic();

            if ($method->comment !== null) {
                $interfaceMethod->addComment((string) $method->comment);
            }

            $phpdocPrefix = ($method->comment !== null ? "\n" : '');

            $in = $this->graph->get($method->inType);
            $out = $this->graph->get($method->outType);

            if (!$method->clientStreaming && !$method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('request')->setType($in->fqcn),
                        new Parameter('md')->setType('Metadata'),
                        new Parameter('cancellation')->setType('Cancellation'),
                    ])
                    ->setReturnType($out->fqcn);
            } elseif ($method->clientStreaming && !$method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('stream')->setType('Server\ClientStreamChannel'),
                        new Parameter('md')->setType('Metadata'),
                        new Parameter('cancellation')->setType('Cancellation'),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\ClientStreamChannel<{$in->fqcn}, {$out->fqcn}> \$stream");
            } elseif (!$method->clientStreaming && $method->serverStreaming) {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('request')->setType($in->fqcn),
                        new Parameter('stream')->setType('Server\ServerStreamChannel'),
                        new Parameter('md')->setType('Metadata'),
                        new Parameter('cancellation')->setType('Cancellation'),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\ServerStreamChannel<{$in->fqcn}, {$out->fqcn}> \$stream");
            } else {
                $interfaceMethod
                    ->setParameters([
                        new Parameter('stream')->setType('Server\BidirectionalStreamChannel'),
                        new Parameter('md')->setType('Metadata'),
                        new Parameter('cancellation')->setType('Cancellation'),
                    ])
                    ->setReturnType('void')
                    ->addComment("{$phpdocPrefix}@param Server\\BidirectionalStreamChannel<{$in->fqcn}, {$out->fqcn}> \$stream");
            }
        }

        return $namespace;
    }

    public function generateServerRegistry(Parser\ServiceDescriptor $service): PhpNamespace
    {
        $namespace = $this->namespacer->create($service->path);

        $classType = new ClassType(\sprintf('%sServerRegistry', Naming::pascalCase($service->name)))
            ->addComment('@api')
            ->setFinal()
            ->setReadOnly()
            ->setImplements([
                'Server\ServiceRegistry',
            ]);

        $namespace->add($classType);

        $namespace->addUse('Thesis\Grpc\Server');

        $handlers = [];

        foreach ($service->methods as $method) {
            $in = $this->graph->get($method->inType);

            $methodName = Naming::camelCase($method->name);

            $args = [
                $method->name,
                new Literal($in->fqcn),
                $methodName,
            ];

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
                    ->setType('\\' . $namespace->getName() . '\\' . \sprintf('%sServer', Naming::pascalCase($service->name)))
                    ->setPrivate(),
            ]);

        $namespace->addUse(\Override::class);

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

        return $namespace;
    }
}
