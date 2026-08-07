<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Registry\File;

/**
 * @api
 */
final readonly class DescriptorMetadataRegistryGenerator
{
    public function __construct(
        private PhpNamespacer $namespacer,
    ) {}

    /**
     * @param list<DescriptorFile> $files the .proto files that share this package
     */
    public function generate(
        string $className,
        array $files,
    ): PhpNamespace {
        $namespace = $this->namespacer->create($className);

        $classType = new ClassType($className)
            ->setFinal()
            ->setReadOnly()
            ->addComment('@api')
            ->setImplements([
                'Registry\Registrar',
            ]);

        foreach ($files as $file) {
            $classType->addMember(
                new Constant($file->constant)
                    ->setPrivate()
                    ->setType('string')
                    ->setValue(base64_encode($file->buffer)),
            );
        }

        $namespace->add($classType);

        $namespace->addUse('Thesis\Protobuf\Registry');
        $namespace->addUse('Thesis\Protobuf\Registry\File');
        $namespace->addUse(\Override::class);

        $method = $classType
            ->addMethod('register')
            ->setPublic()
            ->setParameters([
                new Parameter('pool')->setType('Registry\Pool'),
            ])
            ->setReturnType('void')
            ->addAttribute(\Override::class);

        foreach ($files as $idx => $file) {
            if ($idx > 0) {
                $method->addBody('');
            }

            self::appendFile($method, $file);
        }

        return $namespace;
    }

    private static function appendFile(Method $method, DescriptorFile $file): void
    {
        $method->addBody(\sprintf('$pool->add(Registry\Descriptor::base64(self::%s), new File(', $file->constant));
        $method->addBody('    name: ?,', [$file->name]);

        self::pushParameter($method, 'dependencies', $file->dependencies);
        self::pushParameter(
            $method,
            'messages',
            $file->index->messageTypes,
            static fn(File\MessageDescriptor $descriptor) => new Literal(
                \sprintf("new File\\MessageDescriptor('%s', \\%s::class)", $descriptor->name, $descriptor->fqcn),
            ),
        );
        self::pushParameter($method, 'enums', $file->index->enumTypes, static fn(File\EnumDescriptor $descriptor) => new Literal(
            \sprintf("new File\\EnumDescriptor('%s', \\%s::class)", $descriptor->name, $descriptor->fqcn),
        ));
        self::pushParameter(
            $method,
            'services',
            $file->index->services,
            static fn(File\ServiceDescriptor $descriptor) => new Literal(
                <<<'PHP'
new File\ServiceDescriptor(
            name: ?,
            methods: [
                ?
            ],
        )
PHP,
                [
                    $descriptor->name,
                    new Literal(implode(
                        "\n                ",
                        array_map(
                            static fn(File\MethodDescriptor $method) => \sprintf(
                                "new File\\MethodDescriptor('%s', %s, %s),",
                                $method->name,
                                $method->clientStream ? 'true' : 'false',
                                $method->serverStream ? 'true' : 'false',
                            ),
                            $descriptor->methods,
                        ),
                    )),
                ],
            ),
        );

        $method->addBody('));');
    }

    /**
     * @template V
     * @template R
     * @param non-empty-string $name
     * @param list<V> $items
     * @param ?\Closure(V): R $format
     */
    private static function pushParameter(
        Method $method,
        string $name,
        array $items,
        ?\Closure $format = null,
    ): void {
        $format ??= static fn(mixed $value) => $value;

        if ($items !== []) {
            $method->addBody("    {$name}: [");

            foreach ($items as $value) {
                $method->addBody('        ?,', [$format($value)]);
            }

            $method->addBody('    ],');
        }
    }
}
