<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto\Label;
use Thesis\Google\Protobuf\FieldDescriptorProto\Type;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\OneofDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Generator\ProtoGenerator;

/**
 * A proto2 group is written between start and end group tags, which the runtime does not support. It is reported
 * instead of being generated as a length-prefixed message with an incompatible wire format. The descriptors are
 * what protoc sends for the proto in the comment.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(ProtoGenerator::class)]
final class CompilerGroupTest extends TestCase
{
    #[DataProvider('provideGroupIsReportedCases')]
    public function testGroupIsReported(FieldDescriptorProto $field, ?OneofDescriptorProto $oneof): void
    {
        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage('Message t.M: field result is a group, groups are not supported.');

        new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json')->compile(new CodeGeneratorRequest(
            fileToGenerate: ['t.proto'],
            protoFile: [
                new FileDescriptorProto(
                    name: 't.proto',
                    package: 't',
                    messageType: [
                        new DescriptorProto(
                            name: 'M',
                            field: [$field],
                            nestedType: [
                                new DescriptorProto(
                                    name: 'Result',
                                    field: [
                                        new FieldDescriptorProto(
                                            name: 'url',
                                            number: 2,
                                            label: Label::LABEL_OPTIONAL,
                                            type: Type::TYPE_STRING,
                                            jsonName: 'url',
                                        ),
                                    ],
                                ),
                            ],
                            oneofDecl: $oneof !== null ? [$oneof] : [],
                        ),
                    ],
                ),
            ],
        ));
    }

    /**
     * @return iterable<string, array{FieldDescriptorProto, ?OneofDescriptorProto}>
     */
    public static function provideGroupIsReportedCases(): iterable
    {
        // syntax = "proto2"; message M { optional group Result = 1 { optional string url = 2; } }
        yield 'optional' => [self::group(Label::LABEL_OPTIONAL), null];

        // syntax = "proto2"; message M { required group Result = 1 { optional string url = 2; } }
        yield 'required' => [self::group(Label::LABEL_REQUIRED), null];

        // syntax = "proto2"; message M { repeated group Result = 1 { optional string url = 2; } }
        yield 'repeated' => [self::group(Label::LABEL_REPEATED), null];

        // syntax = "proto2"; message M { oneof o { group Result = 1 { optional string url = 2; } } }
        yield 'oneof variant' => [self::group(Label::LABEL_OPTIONAL, oneofIndex: 0), new OneofDescriptorProto('o')];
    }

    private static function group(Label $label, ?int $oneofIndex = null): FieldDescriptorProto
    {
        return new FieldDescriptorProto(
            name: 'result',
            number: 1,
            label: $label,
            type: Type::TYPE_GROUP,
            typeName: '.t.M.Result',
            oneofIndex: $oneofIndex,
            jsonName: 'result',
        );
    }
}
