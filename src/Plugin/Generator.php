<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

/**
 * @api
 */
final readonly class Generator
{
    private PsrPrinter $printer;

    private Generator\GrpcGenerator $grpc;

    private Generator\ProtoGenerator $proto;

    private Generator\PhpNamespacer $namespacer;

    /**
     * @param non-empty-string $generatedDoc
     */
    public function __construct(
        string $namespace,
        private string $path,
        private string $generatedDoc,
        Dependency\Graph $graph,
        ?string $package = null,
        ?string $syntax = null,
    ) {
        $this->printer = new Printer()->setTypeResolving(false);
        $this->namespacer = new Generator\PhpNamespacer($namespace);
        $this->grpc = new Generator\GrpcGenerator(
            $this->namespacer,
            $graph,
            $package,
        );
        $this->proto = new Generator\ProtoGenerator(
            $graph,
            $this->namespacer,
            $syntax,
        );
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateMessages(Parser\MessageDescriptor $message): iterable
    {
        foreach ($this->proto->generateMessages($message) as $path => $namespace) {
            yield $this->createFile($namespace, $path);
        }
    }

    public function generateEnum(Parser\EnumDescriptor $enum): CodeGeneratorResponse\File
    {
        return $this->createFile($this->proto->generateEnum($enum), $enum->path);
    }

    public function generateGrpcClient(Parser\ServiceDescriptor $service): CodeGeneratorResponse\File
    {
        return $this->createFile($this->grpc->generateClient($service), "{$service->path}Client");
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    public function generateGrpcServer(Parser\ServiceDescriptor $service): iterable
    {
        yield $this->createFile($this->grpc->generateServer($service), "{$service->path}Server");

        if ($service->methods !== []) {
            yield $this->createFile($this->grpc->generateServerRegistry($service), "{$service->path}ServerRegistry");
        }
    }

    private function createFile(
        PhpNamespace $namespace,
        string $path,
    ): CodeGeneratorResponse\File {
        $file = new PhpFile()
            ->setStrictTypes()
            ->setComment($this->generatedDoc)
            ->add($namespace);

        return new CodeGeneratorResponse\File(
            name: \sprintf('%s/%s.php', $this->path, Naming::path($path)),
            content: $this->printer->printFile($file),
        );
    }
}
