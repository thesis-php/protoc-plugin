<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
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

    /**
     * @param list<string> $dependencies
     */
    public function generate(
        NameIndex $index,
        string $filename,
        array $dependencies,
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
        $namespace->addUse('Thesis\Protobuf\Pool\File');
        $namespace->addUse(\Override::class);

        $method = $classType
            ->addMethod('register')
            ->setPublic()
            ->setParameters([
                new Parameter('pool')->setType('Pool\Registry'),
            ])
            ->setReturnType('void')
            ->addAttribute(\Override::class);

        $method->addBody('$pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), new File(');
        $method->addBody('    name: ?,', [$filename]);

        self::pushParameter($method, 'dependencies', $dependencies);
        self::pushParameter(
            $method,
            'messages',
            $index->messageTypes,
            static fn(string $type, string $fqcn) => new Literal(
                \sprintf("new File\\MessageDescriptor('%s', \\%s::class)", $type, $fqcn),
            ),
        );
        self::pushParameter($method, 'enums', $index->enumTypes, static fn(string $type, string $fqcn) => new Literal(
            \sprintf("new File\\EnumDescriptor('%s', \\%s::class)", $type, $fqcn),
        ));
        self::pushParameter(
            $method,
            'services',
            $index->grpc,
            static fn(string $type, object $service) => new Literal(
                vsprintf(
                    <<<'PHP'
        new File\ServiceDescriptor(
            name: '%s',
            clientFqcn: %s,
            serverFqcn: %s,
        )
PHP,
                    [
                        $type,
                        isset($service->client) ? "\\{$service->client}::class" : 'null',
                        isset($service->server) ? "\\{$service->server}::class" : 'null',
                    ],
                ),
            ),
        );

        $method->addBody('));');

        return $namespace;
    }

    /**
     * @template K of array-key
     * @template V
     * @template R
     * @param non-empty-string $name
     * @param array<K, V> $items
     * @param ?\Closure(K, V): R $format
     */
    private static function pushParameter(
        Method $method,
        string $name,
        array $items,
        ?\Closure $format = null,
    ): void {
        $format ??= static fn(mixed $key, mixed $value) => $value;

        if ($items !== []) {
            $method->addBody("    {$name}: [");

            foreach ($items as $key => $value) {
                $method->addBody('        ?,', [$format($key, $value)]);
            }

            $method->addBody('    ],');
        }
    }
}
