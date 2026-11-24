<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse\File;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\FileOptions;
use Thesis\Google\Protobuf\MethodDescriptorProto;
use Thesis\Google\Protobuf\ServiceDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\OutputTable;

/**
 * The src_path option puts every namespace into one directory, the php_namespace option puts every package into
 * one namespace. Files of several packages generated in one run may then write two classes to one file, which is
 * reported instead of protoc's "Tried to write the same file twice". Files of several packages whose classes do
 * not collide are generated as before. Snapshots are generated without options, so these are unit tests.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(OutputTable::class)]
final class CompilerMultiplePackagesTest extends TestCase
{
    /**
     * @param list<FileDescriptorProto> $files
     */
    #[DataProvider('provideCollisionIsReportedCases')]
    public function testCollisionIsReported(string $parameter, array $files, string $error): void
    {
        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage($error);

        self::compile($parameter, $files);
    }

    /**
     * @return iterable<string, array{string, list<FileDescriptorProto>, string}>
     */
    public static function provideCollisionIsReportedCases(): iterable
    {
        yield 'src_path and one message name in two packages' => [
            'src_path=gen',
            [self::file('a.proto', 'a.v1', ['M']), self::file('b.proto', 'b.v1', ['M'])],
            'Files a.proto (package a.v1, namespace A\V1) and b.proto (package b.v1, namespace B\V1) both generate gen/M.php. The src_path=gen option puts the classes of every namespace into one directory, generate these packages in separate protoc runs with their own src_path.',
        ];

        yield 'src_path, a grpc client and a message' => [
            'src_path=.',
            [self::file('a.proto', 'a.v1', ['Req'], service: 'Lookup'), self::file('b.proto', null, ['LookupClient'], phpNamespace: 'B')],
            'Files a.proto (package a.v1, namespace A\V1) and b.proto (no package, namespace B) both generate ./LookupClient.php. The src_path=. option',
        ];

        yield 'php_namespace and one message name in two packages' => [
            'php_namespace=App\V1',
            [self::file('a.proto', 'a.v1', ['M']), self::file('b.proto', 'b.v1', ['M'])],
            'Files a.proto (package a.v1, namespace App\V1) and b.proto (package b.v1, namespace App\V1) both generate App/V1/M.php. The php_namespace=App\V1 option puts the classes of every package into one namespace, generate these packages in separate protoc runs with their own php_namespace.',
        ];

        yield 'php_namespace, a grpc server and a message' => [
            'php_namespace=App,grpc=server',
            [self::file('a.proto', 'a.v1', ['Req'], service: 'Lookup'), self::file('b.proto', 'b.v1', ['LookupServerRegistry'])],
            'Files a.proto (package a.v1, namespace App) and b.proto (package b.v1, namespace App) both generate App/LookupServerRegistry.php.',
        ];

        yield 'src_path and php_namespace' => [
            'src_path=gen,php_namespace=App',
            [self::file('a.proto', 'a.v1', ['M']), self::file('b.proto', 'b.v1', ['M'])],
            'Files a.proto (package a.v1, namespace App) and b.proto (package b.v1, namespace App) both generate gen/M.php. The php_namespace=App option',
        ];

        yield 'one php_namespace file option in two packages' => [
            '',
            [self::file('a.proto', 'a.v1', ['M'], phpNamespace: 'Shared'), self::file('b.proto', 'b.v1', ['M'], phpNamespace: 'Shared')],
            'Files a.proto (package a.v1, namespace Shared) and b.proto (package b.v1, namespace Shared) both generate Shared/M.php.',
        ];
    }

    /**
     * @param list<FileDescriptorProto> $files
     * @param list<string> $expected generated files
     */
    #[DataProvider('provideNoCollisionCases')]
    public function testNoCollision(string $parameter, array $files, array $expected): void
    {
        $generated = array_map(
            static fn(File $file): ?string => $file->name,
            self::compile($parameter, $files),
        );

        sort($generated);

        self::assertSame($expected, $generated);
    }

    /**
     * @return iterable<string, array{string, list<FileDescriptorProto>, list<string>}>
     */
    public static function provideNoCollisionCases(): iterable
    {
        // The files of every namespace share the directory and a single registry, as before.
        yield 'src_path' => [
            'src_path=gen',
            [self::file('a.proto', 'a.v1', ['M']), self::file('c.proto', 'c.v1', ['OnlyC'])],
            ['gen/DescriptorRegistry.php', 'gen/M.php', 'gen/OnlyC.php', 'gen/autoload.metadata.php'],
        ];

        yield 'php_namespace' => [
            'php_namespace=App\V1',
            [self::file('a.proto', 'a.v1', ['M']), self::file('c.proto', 'c.v1', ['OnlyC'])],
            ['App/V1/DescriptorRegistry.php', 'App/V1/M.php', 'App/V1/OnlyC.php', 'App/V1/autoload.metadata.php'],
        ];

        yield 'one message name in two packages without options' => [
            '',
            [self::file('a.proto', 'a.v1', ['M']), self::file('b.proto', 'b.v1', ['M'])],
            ['A/V1/DescriptorRegistry.php', 'A/V1/M.php', 'A/V1/autoload.metadata.php', 'B/V1/DescriptorRegistry.php', 'B/V1/M.php', 'B/V1/autoload.metadata.php'],
        ];
    }

    /**
     * @param list<FileDescriptorProto> $files
     * @return list<File>
     */
    private static function compile(string $parameter, array $files): array
    {
        $request = new CodeGeneratorRequest(
            fileToGenerate: array_map(static fn(FileDescriptorProto $file): string => (string) $file->name, $files),
            parameter: $parameter,
            protoFile: $files,
        );

        return new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json')->compile($request);
    }

    /**
     * @param non-empty-list<string> $messages
     */
    private static function file(
        string $name,
        ?string $package,
        array $messages,
        ?string $service = null,
        ?string $phpNamespace = null,
    ): FileDescriptorProto {
        $type = '.' . ($package !== null ? "{$package}." : '') . $messages[0];

        return new FileDescriptorProto(
            name: $name,
            package: $package,
            messageType: array_map(static fn(string $message): DescriptorProto => new DescriptorProto(name: $message), $messages),
            service: $service === null ? [] : [
                new ServiceDescriptorProto(
                    name: $service,
                    method: [new MethodDescriptorProto(name: 'Get', inputType: $type, outputType: $type)],
                ),
            ],
            options: $phpNamespace === null ? null : new FileOptions(phpNamespace: $phpNamespace),
            syntax: 'proto3',
        );
    }
}
