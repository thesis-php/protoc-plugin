<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protoc\Plugin\NameIndex;

/**
 * @api
 */
final readonly class DescriptorMetadataRegistryGenerator
{
    public function __construct(
        private PhpNamespacer $namespacer,
    ) {}

    public function generate(
        NameIndex $index,
        string $className,
        string $buffer,
    ): PhpNamespace {
        $namespace = $this->namespacer->create($className);

        $classType = new ClassType($className)
            ->setFinal()
            ->setReadOnly()
            ->addComment('@api')
            ->setImplements([
                'Pool\Registrar',
            ])
            ->addMember(
                new Constant('DESCRIPTOR_BUFFER')
                    ->setPrivate()
                    ->setType('string')
                    ->setValue(base64_encode($buffer)),
            );

        $namespace->add($classType);

        $namespace->addUse('Thesis\Protobuf\Pool');
        $namespace->addUse(\Override::class);

        $classType
            ->addMethod('register')
            ->setPublic()
            ->setParameters([
                new Parameter('pool')->setType('Pool\Registry'),
            ])
            ->setReturnType('void')
            ->addAttribute(\Override::class)
            ->setBody(
                \sprintf(
                    <<<'PHP'
$pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
    %s,
]);
PHP,
                    implode(",\n\t", array_fill(0, \count($index), '?')),
                ),
                array_merge(
                    array_map(
                        static fn(string $type, string $fqcn) => new Literal(\sprintf("'%s' => new Pool\\MessageMetadata(\\%s::class)", $type, $fqcn)),
                        array_keys($index->messageTypes),
                        array_values($index->messageTypes),
                    ),
                    array_map(
                        static fn(string $type, string $fqcn) => new Literal(\sprintf("'%s' => new Pool\\EnumMetadata(\\%s::class)", $type, $fqcn)),
                        array_keys($index->enumTypes),
                        array_values($index->enumTypes),
                    ),
                ),
            );

        return $namespace;
    }
}
