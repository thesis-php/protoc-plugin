<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\FileOptions;
use Thesis\Protoc\Plugin\CompilerOptions;
use Thesis\Protoc\Plugin\Mapping\TypeMap;
use Thesis\Protoc\Plugin\NamespaceResolver;
use Thesis\Protoc\Plugin\Parser;

/**
 * Which types a file sees: its own, the ones of every file it imports, and transitively
 * everything those files re-export through "import public". The descriptors are the ones
 * protoc sends for the schemas in the comments; only a.proto is generated.
 */
#[CoversClass(Registry::class)]
final class RegistryTest extends TestCase
{
    /**
     * @param list<FileDescriptorProto> $files
     * @param array<string, string> $expected type name => fqcn
     */
    #[DataProvider('provideVisibleTypesCases')]
    public function testVisibleTypes(array $files, array $expected): void
    {
        foreach ([$files, array_reverse($files)] as $order) {
            $graph = self::registry($order)->graph('a.proto');

            foreach ($expected as $typeName => $fqcn) {
                self::assertSame($fqcn, $graph->get($typeName)->fqcn);
            }
        }
    }

    /**
     * @return iterable<string, array{list<FileDescriptorProto>, array<string, string>}>
     */
    public static function provideVisibleTypesCases(): iterable
    {
        // a.proto: import "b.proto"; message A { t.c.C c = 1; t.d.D d = 2; }
        // b.proto: import public "c.proto";
        // c.proto: import public "d.proto"; message C {}
        // d.proto: message D {}
        yield 'public imports at any depth' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.c.C', '.t.d.D'])]),
                self::file('b.proto', 't.b', ['c.proto'], public: [0]),
                self::file('c.proto', 't.c', ['d.proto'], public: [0], messages: [self::message('C')]),
                self::file('d.proto', 't.d', messages: [self::message('D')]),
            ],
            ['.t.c.C' => '\T\C\C', '.t.d.D' => '\T\D\D'],
        ];

        // a.proto: import "b.proto"; import "e.proto"; message A { t.c.C c = 1; t.d.D d = 2; }
        // b.proto, e.proto: import public "c.proto";
        // c.proto: import public "d.proto"; message C {}
        yield 'diamond' => [
            [
                self::file('a.proto', 't.a', ['b.proto', 'e.proto'], messages: [self::message('A', ['.t.c.C', '.t.d.D'])]),
                self::file('b.proto', 't.b', ['c.proto'], public: [0]),
                self::file('e.proto', 't.e', ['c.proto'], public: [0]),
                self::file('c.proto', 't.c', ['d.proto'], public: [0], messages: [self::message('C')]),
                self::file('d.proto', 't.d', messages: [self::message('D')]),
            ],
            ['.t.c.C' => '\T\C\C', '.t.d.D' => '\T\D\D'],
        ];

        // protoc rejects cyclic imports, the registry still must not loop on them.
        yield 'cycle of public imports' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.b.B', '.t.c.C'])]),
                self::file('b.proto', 't.b', ['c.proto'], public: [0], messages: [self::message('B')]),
                self::file('c.proto', 't.c', ['b.proto'], public: [0], messages: [self::message('C')]),
            ],
            ['.t.b.B' => '\T\B\B', '.t.c.C' => '\T\C\C'],
        ];

        // A type keeps the namespace of the file that declares it, not of the re-exporting one.
        // b.proto: option php_namespace = "Via\\B"; import public "c.proto";
        // c.proto: option php_namespace = "Custom\\C"; message C { message Inner {} }
        yield 'php_namespace of the declaring file' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.c.C', '.t.c.C.Inner'])]),
                self::file('b.proto', 't.b', ['c.proto'], public: [0], phpNamespace: 'Via\B'),
                self::file('c.proto', 't.c', phpNamespace: 'Custom\C', messages: [
                    new DescriptorProto(name: 'C', nestedType: [new DescriptorProto(name: 'Inner')]),
                ]),
            ],
            ['.t.c.C' => '\Custom\C\C', '.t.c.C.Inner' => '\Custom\C\C\Inner'],
        ];

        // A re-exported type relocated by map.json.
        // b.proto: import public "google/type/date.proto";
        yield 'type relocated by map.json' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.google.type.Date'])]),
                self::file('b.proto', 't.b', ['google/type/date.proto'], public: [0]),
                self::file('google/type/date.proto', 'google.type', messages: [self::message('Date')]),
            ],
            ['.google.type.Date' => '\Thesis\Google\Type\Date'],
        ];

        // Only the public one of the imports of b is re-exported.
        // b.proto: import "x.proto"; import public "c.proto";
        yield 'public index among plain imports' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.c.C'])]),
                self::file('b.proto', 't.b', ['x.proto', 'c.proto'], public: [1]),
                self::file('x.proto', 't.x', messages: [self::message('X')]),
                self::file('c.proto', 't.c', messages: [self::message('C')]),
            ],
            ['.t.c.C' => '\T\C\C'],
        ];
    }

    /**
     * @param list<FileDescriptorProto> $files
     */
    #[DataProvider('provideInvisibleTypesCases')]
    public function testInvisibleTypes(array $files, string $typeName): void
    {
        $graph = self::registry($files)->graph('a.proto');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No type '{$typeName}' was found.");

        $graph->get($typeName);
    }

    /**
     * protoc rejects these schemas itself, the registry must not be wider than the import semantics either.
     *
     * @return iterable<string, array{list<FileDescriptorProto>, string}>
     */
    public static function provideInvisibleTypesCases(): iterable
    {
        // b.proto: import "c.proto";
        yield 'plain import is not re-exported' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.c.C'])]),
                self::file('b.proto', 't.b', ['c.proto']),
                self::file('c.proto', 't.c', messages: [self::message('C')]),
            ],
            '.t.c.C',
        ];

        // b.proto: import public "c.proto"; c.proto: import "d.proto";
        yield 'plain import behind a public one is not re-exported' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.d.D'])]),
                self::file('b.proto', 't.b', ['c.proto'], public: [0]),
                self::file('c.proto', 't.c', ['d.proto']),
                self::file('d.proto', 't.d', messages: [self::message('D')]),
            ],
            '.t.d.D',
        ];

        // b.proto: import "x.proto"; import public "c.proto";
        yield 'plain import next to a public one is not re-exported' => [
            [
                self::file('a.proto', 't.a', ['b.proto'], messages: [self::message('A', ['.t.x.X'])]),
                self::file('b.proto', 't.b', ['x.proto', 'c.proto'], public: [1]),
                self::file('x.proto', 't.x', messages: [self::message('X')]),
                self::file('c.proto', 't.c', messages: [self::message('C')]),
            ],
            '.t.x.X',
        ];
    }

    /**
     * @param list<FileDescriptorProto> $files
     */
    private static function registry(array $files): Registry
    {
        $request = new CodeGeneratorRequest(fileToGenerate: ['a.proto'], protoFile: $files);

        return new Registry(
            new Parser()->parse($request),
            new NamespaceResolver(
                TypeMap::fromFile(__DIR__ . '/../../../map.json'),
                CompilerOptions::fromRequest($request),
            ),
        );
    }

    /**
     * @param list<string> $dependencies
     * @param list<int> $public
     * @param list<DescriptorProto> $messages
     */
    private static function file(
        string $name,
        string $package,
        array $dependencies = [],
        array $public = [],
        ?string $phpNamespace = null,
        array $messages = [],
    ): FileDescriptorProto {
        return new FileDescriptorProto(
            name: $name,
            package: $package,
            dependency: $dependencies,
            messageType: $messages,
            publicDependency: $public,
            options: $phpNamespace === null ? null : new FileOptions(phpNamespace: $phpNamespace),
            syntax: 'proto3',
        );
    }

    /**
     * @param list<string> $fieldTypes type names of the message fields
     */
    private static function message(string $name, array $fieldTypes = []): DescriptorProto
    {
        $fields = [];

        foreach ($fieldTypes as $idx => $typeName) {
            $fields[] = new FieldDescriptorProto(
                name: "f{$idx}",
                number: $idx + 1,
                label: FieldDescriptorProto\Label::LABEL_OPTIONAL,
                type: FieldDescriptorProto\Type::TYPE_MESSAGE,
                typeName: $typeName,
                jsonName: "f{$idx}",
            );
        }

        return new DescriptorProto(name: $name, field: $fields);
    }
}
