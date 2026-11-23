<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\Edition;
use Thesis\Google\Protobuf\FeatureSet;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto\Label;
use Thesis\Google\Protobuf\FieldDescriptorProto\Type;
use Thesis\Google\Protobuf\FieldOptions;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Google\Protobuf\OneofDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Generator\ProtoGenerator;

/**
 * A oneof variant is generated from the field as declared, features included: a DELIMITED message variant is
 * reported the same as a DELIMITED message field, instead of being generated as a length-prefixed message.
 * The descriptors are what protoc sends for the proto in the comment. The rest of the field metadata a variant
 * keeps is covered by the oneof_variants snapshot case.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(ProtoGenerator::class)]
final class CompilerOneofVariantTest extends TestCase
{
    #[DataProvider('provideDelimitedMessageIsReportedCases')]
    public function testDelimitedMessageIsReported(?int $oneofIndex): void
    {
        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage('DELIMITED message encoding are not supported');

        self::compile($oneofIndex);
    }

    /**
     * @return iterable<string, array{?int}>
     */
    public static function provideDelimitedMessageIsReportedCases(): iterable
    {
        // edition = "2023"; message M { message Inner { int32 a = 1; } Inner d = 1 [features.message_encoding = DELIMITED]; }
        yield 'field' => [null];

        // edition = "2023"; message M { message Inner { int32 a = 1; } oneof o { Inner d = 1 [features.message_encoding = DELIMITED]; } }
        yield 'oneof variant' => [0];
    }

    private static function compile(?int $oneofIndex): void
    {
        new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json')->compile(new CodeGeneratorRequest(
            fileToGenerate: ['t.proto'],
            protoFile: [
                new FileDescriptorProto(
                    name: 't.proto',
                    package: 't',
                    messageType: [
                        new DescriptorProto(
                            name: 'M',
                            field: [
                                new FieldDescriptorProto(
                                    name: 'd',
                                    number: 1,
                                    label: Label::LABEL_OPTIONAL,
                                    type: Type::TYPE_MESSAGE,
                                    typeName: '.t.M.Inner',
                                    oneofIndex: $oneofIndex,
                                    jsonName: 'd',
                                    options: new FieldOptions(
                                        features: new FeatureSet(messageEncoding: FeatureSet\MessageEncoding::DELIMITED),
                                    ),
                                ),
                            ],
                            nestedType: [
                                new DescriptorProto(
                                    name: 'Inner',
                                    field: [
                                        new FieldDescriptorProto(
                                            name: 'a',
                                            number: 1,
                                            label: Label::LABEL_OPTIONAL,
                                            type: Type::TYPE_INT32,
                                            jsonName: 'a',
                                        ),
                                    ],
                                ),
                            ],
                            oneofDecl: $oneofIndex !== null ? [new OneofDescriptorProto('o')] : [],
                        ),
                    ],
                    syntax: 'editions',
                    edition: Edition::EDITION_2023,
                ),
            ],
        ));
    }
}
