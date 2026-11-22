<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\EnumDescriptorProto;
use Thesis\Google\Protobuf\EnumValueDescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\OneofDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Generator\ProtoGenerator;

/**
 * A oneof variant class ({Oneof}{Field}) that clashes with another class of the message namespace is reported,
 * not renamed. protoc checks only proto names, so each schema here is accepted by protoc (the descriptors are
 * what it sends for the proto in the comment) and would otherwise end in "Tried to write the same file twice",
 * or, for names differing only in case, in two PHP classes PHP considers one.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(ProtoGenerator::class)]
final class CompilerOneofCollisionTest extends TestCase
{
    #[DataProvider('provideVariantCollisionIsReportedCases')]
    public function testVariantCollisionIsReported(DescriptorProto $message, string $error): void
    {
        $request = new CodeGeneratorRequest(
            fileToGenerate: ['t.proto'],
            protoFile: [
                new FileDescriptorProto(
                    name: 't.proto',
                    package: 't',
                    messageType: [$message],
                    syntax: 'proto3',
                ),
            ],
        );

        $compiler = new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json');

        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage($error);

        $compiler->compile($request);
    }

    /**
     * @return iterable<string, array{DescriptorProto, string}>
     */
    public static function provideVariantCollisionIsReportedCases(): iterable
    {
        // message A { message PayloadText {} oneof payload { string text = 1; } }
        yield 'variant and nested message' => [
            new DescriptorProto(
                name: 'A',
                field: [self::stringField('text', 1, 0)],
                nestedType: [new DescriptorProto(name: 'PayloadText')],
                oneofDecl: [new OneofDescriptorProto('payload')],
            ),
            'Message t.A: class PayloadText for the variant text of the oneof payload conflicts with the nested message PayloadText.',
        ];

        // message A { message PAYLOADTEXT {} oneof payload { string text = 1; } }
        yield 'variant and nested message differing in case' => [
            new DescriptorProto(
                name: 'A',
                field: [self::stringField('text', 1, 0)],
                nestedType: [new DescriptorProto(name: 'PAYLOADTEXT')],
                oneofDecl: [new OneofDescriptorProto('payload')],
            ),
            'Message t.A: class PayloadText for the variant text of the oneof payload conflicts with the nested message PAYLOADTEXT.',
        ];

        // message A { enum PayloadText { PAYLOAD_TEXT_UNSPECIFIED = 0; } oneof payload { string text = 1; } }
        yield 'variant and nested enum' => [
            new DescriptorProto(
                name: 'A',
                field: [self::stringField('text', 1, 0)],
                enumType: [new EnumDescriptorProto(
                    name: 'PayloadText',
                    value: [new EnumValueDescriptorProto('PAYLOAD_TEXT_UNSPECIFIED', 0)],
                )],
                oneofDecl: [new OneofDescriptorProto('payload')],
            ),
            'Message t.A: class PayloadText for the variant text of the oneof payload conflicts with the nested enum PayloadText.',
        ];

        // message A { oneof a { string b_c = 1; } oneof a_b { string c = 2; } }
        yield 'variants of two oneofs' => [
            new DescriptorProto(
                name: 'A',
                field: [self::stringField('b_c', 1, 0), self::stringField('c', 2, 1)],
                oneofDecl: [new OneofDescriptorProto('a'), new OneofDescriptorProto('a_b')],
            ),
            'Message t.A: class ABC for the variant c of the oneof a_b conflicts with the variant b_c of the oneof a.',
        ];

        // message A { oneof a { string b_c = 1; } oneof a_b_c { string d = 2; } }
        yield 'variant and interface of another oneof' => [
            new DescriptorProto(
                name: 'A',
                field: [self::stringField('b_c', 1, 0), self::stringField('d', 2, 1)],
                oneofDecl: [new OneofDescriptorProto('a'), new OneofDescriptorProto('a_b_c')],
            ),
            'Message t.A: class ABC for the variant b_c of the oneof a conflicts with the interface of the oneof a_b_c.',
        ];
    }

    /**
     * A string field of a oneof, with the json_name protoc fills in.
     */
    private static function stringField(string $name, int $number, int $oneofIndex): FieldDescriptorProto
    {
        return new FieldDescriptorProto(
            name: $name,
            number: $number,
            label: FieldDescriptorProto\Label::LABEL_OPTIONAL,
            type: FieldDescriptorProto\Type::TYPE_STRING,
            oneofIndex: $oneofIndex,
            jsonName: lcfirst(str_replace('_', '', ucwords($name, '_'))),
        );
    }
}
