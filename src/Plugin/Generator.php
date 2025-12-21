<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;

/**
 * @api
 */
final readonly class Generator
{
    private PsrPrinter $printer;

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $path
     */
    public function __construct(
        public string $namespace,
        public string $path,
    ) {
        $this->printer = new PsrPrinter();
    }

    public function generateEnum(EnumDescriptor $enum): CodeGeneratorResponse\File
    {
        $doc = '@api';
        if ($enum->comment !== null) {
            $doc .= "\n\n{$enum->comment}";
        }

        $enumType = new EnumType($enum->name)
            ->setComment($doc)
            ->setType('int')
            ->setCases(array_map(
                static fn(EnumCaseDescriptor $case) => new EnumCase($case->name)
                    ->setValue($case->value)
                    ->setComment((string) $case->comment),
                $enum->cases,
            ));

        return $this->createFile($enumType, $enum->path);
    }

    private function createFile(ClassType|EnumType|InterfaceType $type, string $path): CodeGeneratorResponse\File
    {
        $namespace = $this->namespace;

        $paths = explode('.', $path);
        $typeNamespace = \array_slice($paths, 0, \count($paths) - 1);
        if (\count($typeNamespace) > 0) {
            $namespace .= '\\' . implode('\\', $typeNamespace);
        }

        $phpNamespace = new PhpNamespace($namespace);

        $file = new PhpFile()
            ->setStrictTypes()
            ->add($phpNamespace->add($type));

        return new CodeGeneratorResponse\File(
            name: \sprintf('%s/%s.php', $this->path, str_replace('.', '/', $path)),
            content: $this->printer->printFile($file),
        );
    }
}
