<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\OneofDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Generator\ProtoGenerator;

/**
 * Two fields or oneofs of a message that end up with the same property are reported, not renamed: otherwise one
 * constructor parameter silently replaces the other and the field is lost. protoc accepts each schema here (the
 * descriptors are what it sends for the proto in the comment; in proto2 clashing JSON names are only a warning).
 */
#[CoversClass(Compiler::class)]
#[CoversClass(ProtoGenerator::class)]
final class CompilerPropertyNameCollisionTest extends TestCase
{
    #[DataProvider('providePropertyCollisionIsReportedCases')]
    public function testPropertyCollisionIsReported(DescriptorProto $message, string $syntax, string $error): void
    {
        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage($error);

        self::compile($message, $syntax);
    }

    /**
     * @return iterable<string, array{DescriptorProto, string, string}>
     */
    public static function providePropertyCollisionIsReportedCases(): iterable
    {
        // syntax = "proto2"; message A { optional string a = 1 [json_name = "b"]; optional string b = 2; }
        yield 'custom json_name and the json_name of another field' => [
            new DescriptorProto(
                name: 'A',
                field: [self::field('a', 1, jsonName: 'b'), self::field('b', 2)],
            ),
            'proto2',
            'Message t.A: the field a and the field b both become the property $b.',
        ];

        // message A { string fooBar = 1; oneof foo_bar { string x = 2; } }
        yield 'field and oneof' => [
            new DescriptorProto(
                name: 'A',
                field: [self::field('fooBar', 1), self::field('x', 2, oneofIndex: 0)],
                oneofDecl: [new OneofDescriptorProto('foo_bar')],
            ),
            'proto3',
            'Message t.A: the field fooBar and the oneof foo_bar both become the property $fooBar.',
        ];

        // message A { string this = 1; string a = 2 [json_name = "this_"]; }
        yield 'escaped this and a field named this_' => [
            new DescriptorProto(
                name: 'A',
                field: [self::field('this', 1), self::field('a', 2, jsonName: 'this_')],
            ),
            'proto3',
            'Message t.A: the field this and the field a both become the property $this_.',
        ];

        // message A { string a = 1 [json_name = "this_"]; oneof this { string b = 2; } }
        yield 'field named this_ and escaped oneof this' => [
            new DescriptorProto(
                name: 'A',
                field: [self::field('a', 1, jsonName: 'this_'), self::field('b', 2, oneofIndex: 0)],
                oneofDecl: [new OneofDescriptorProto('this')],
            ),
            'proto3',
            'Message t.A: the field a and the oneof this both become the property $this_.',
        ];

        // message A { string my_field = 1 [json_name = "my-field"]; string other = 2 [json_name = "myField"]; }
        yield 'name derived from an invalid json_name and the json_name of another field' => [
            new DescriptorProto(
                name: 'A',
                field: [self::field('my_field', 1, jsonName: 'my-field'), self::field('other', 2, jsonName: 'myField')],
            ),
            'proto3',
            'Message t.A: the field my_field and the field other both become the property $myField.',
        ];
    }

    /**
     * message A { string a = 1 [json_name = "Foo"]; string foo = 2; }: PHP variable names are case-sensitive.
     */
    public function testPropertiesDifferingInCaseDoNotCollide(): void
    {
        $files = self::compile(
            new DescriptorProto(
                name: 'A',
                field: [self::field('a', 1, jsonName: 'Foo'), self::field('foo', 2)],
            ),
            'proto3',
        );

        self::assertArrayHasKey('T/A.php', $files);
        self::assertStringContainsString('public string $Foo = \'\',', $files['T/A.php']);
        self::assertStringContainsString('public string $foo = \'\',', $files['T/A.php']);
    }

    /**
     * @return array<string, string> file name => content
     */
    private static function compile(DescriptorProto $message, string $syntax): array
    {
        $request = new CodeGeneratorRequest(
            fileToGenerate: ['t.proto'],
            protoFile: [
                new FileDescriptorProto(
                    name: 't.proto',
                    package: 't',
                    messageType: [$message],
                    syntax: $syntax === 'proto2' ? null : $syntax,
                ),
            ],
        );

        $compiler = new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json');

        $files = [];

        foreach ($compiler->compile($request) as $file) {
            $files[(string) $file->name] = (string) $file->content;
        }

        return $files;
    }

    /**
     * A string field with the json_name protoc fills in: the custom one, or the field name in lowerCamelCase.
     */
    private static function field(string $name, int $number, ?string $jsonName = null, ?int $oneofIndex = null): FieldDescriptorProto
    {
        return new FieldDescriptorProto(
            name: $name,
            number: $number,
            label: FieldDescriptorProto\Label::LABEL_OPTIONAL,
            type: FieldDescriptorProto\Type::TYPE_STRING,
            oneofIndex: $oneofIndex,
            jsonName: $jsonName ?? lcfirst(str_replace('_', '', ucwords($name, '_'))),
        );
    }
}
