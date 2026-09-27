<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Google\Protobuf\Edition;
use Thesis\Protoc\Plugin\Generator\FileFactory;

/**
 * @api
 */
final readonly class ClassLikeGenerator
{
    private Generator\GrpcGenerator $grpc;

    private Generator\ProtoGenerator $proto;

    public function __construct(
        string $namespace,
        private FileFactory $files,
        Dependency\Graph $graph,
        NameIndex $index,
        ?string $package = null,
        ?string $syntax = null,
        ?Edition $edition = null,
        Mapping\TypeMap $types = new Mapping\TypeMap(),
    ) {
        $namespacer = new Generator\PhpNamespacer($namespace, $types, $package);
        $this->grpc = new Generator\GrpcGenerator(
            $namespacer,
            $graph,
            $package,
        );
        $this->proto = new Generator\ProtoGenerator(
            $graph,
            $index,
            $namespacer,
            $syntax,
            $edition,
        );
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateMessages(Parser\MessageDescriptor $message): iterable
    {
        foreach ($this->proto->generateMessages($message) as $path => $namespace) {
            yield $this->files->create($namespace, $path);
        }
    }

    public function generateEnum(Parser\EnumDescriptor $enum): CodeGeneratorResponse\File
    {
        return $this->files->create($this->proto->generateEnum($enum), $enum->path);
    }

    public function generateGrpcClient(Parser\ServiceDescriptor $service): CodeGeneratorResponse\File
    {
        return $this->files->create($this->grpc->generateClient($service), "{$service->path}Client");
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateGrpcServer(Parser\ServiceDescriptor $service): iterable
    {
        yield $this->files->create($this->grpc->generateServer($service), "{$service->path}Server");

        if ($service->methods !== []) {
            yield $this->files->create($this->grpc->generateServerRegistry($service), "{$service->path}ServerRegistry");
        }
    }
}
