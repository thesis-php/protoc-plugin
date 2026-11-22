<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Google\Protobuf\DescriptorProto;
use Thesis\Google\Protobuf\EnumDescriptorProto;
use Thesis\Google\Protobuf\EnumValueDescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto;
use Thesis\Google\Protobuf\FieldDescriptorProto\Type;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Generator\ProtoGenerator;

/**
 * A declared default that cannot be read for the type of its field is reported, instead of silently becoming
 * 0 or false. protoc validates defaults itself, so such descriptors can only come from another source.
 * The valid defaults are covered by the field_defaults snapshot case.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(ProtoGenerator::class)]
final class CompilerDefaultValueTest extends TestCase
{
    #[DataProvider('provideInvalidDefaultIsReportedCases')]
    public function testInvalidDefaultIsReported(Type $type, string $default, string $error): void
    {
        $this->expectException(CodeCannotBeGenerated::class);
        $this->expectExceptionMessage("Message t.M: field f has the default value \"{$error}\" that is not valid for {$type->name}.");

        self::compile($type, $default);
    }

    /**
     * @return iterable<string, array{Type, string, string}>
     */
    public static function provideInvalidDefaultIsReportedCases(): iterable
    {
        yield 'double, not a number' => [Type::TYPE_DOUBLE, 'abc', 'abc'];
        yield 'double, empty' => [Type::TYPE_DOUBLE, '', ''];
        yield 'double, surrounded by spaces' => [Type::TYPE_DOUBLE, ' 1.5', ' 1.5'];
        yield 'double, out of range' => [Type::TYPE_DOUBLE, '1e400', '1e400'];
        yield 'float, out of range' => [Type::TYPE_FLOAT, '1e39', '1e39'];
        yield 'int32, fraction' => [Type::TYPE_INT32, '1.5', '1.5'];
        yield 'int32, above the maximum' => [Type::TYPE_INT32, '2147483648', '2147483648'];
        yield 'sint32, below the minimum' => [Type::TYPE_SINT32, '-2147483649', '-2147483649'];
        yield 'uint32, negative' => [Type::TYPE_UINT32, '-1', '-1'];
        yield 'fixed32, above the maximum' => [Type::TYPE_FIXED32, '4294967296', '4294967296'];
        yield 'int64, above the maximum' => [Type::TYPE_INT64, '9223372036854775808', '9223372036854775808'];
        yield 'sfixed64, below the minimum' => [Type::TYPE_SFIXED64, '-9223372036854775809', '-9223372036854775809'];
        yield 'int64, hexadecimal' => [Type::TYPE_INT64, '0x10', '0x10'];
        yield 'uint64, negative' => [Type::TYPE_UINT64, '-1', '-1'];
        yield 'fixed64, above the maximum' => [Type::TYPE_FIXED64, '18446744073709551616', '18446744073709551616'];
        yield 'bool, not true or false' => [Type::TYPE_BOOL, 'yes', 'yes'];
        yield 'bytes, unknown escape' => [Type::TYPE_BYTES, 'a\q', 'a\\\q'];
        yield 'bytes, trailing backslash' => [Type::TYPE_BYTES, 'a\\', 'a\\\\'];
        yield 'bytes, octal above a byte' => [Type::TYPE_BYTES, '\400', '\\\400'];
        yield 'bytes, hex without digits' => [Type::TYPE_BYTES, '\xg', '\\\xg'];
        yield 'enum, no such value' => [Type::TYPE_ENUM, 'C', 'C'];
        yield 'enum, secured name instead of the value' => [Type::TYPE_ENUM, 'class_', 'class_'];
        yield 'message' => [Type::TYPE_MESSAGE, 'x', 'x'];
    }

    /**
     * protoc escapes bytes with octal \NNN, \n, \r, \t, \", \', \\ only; the rest of the C escapes are decoded too.
     */
    public function testOtherCEscapesOfBytesAreDecoded(): void
    {
        $content = '';

        foreach (self::compile(Type::TYPE_BYTES, '\x41\x7\a\b\f\v\?\0\12') as $file) {
            if ($file->name === 'T/M.php') {
                $content = (string) $file->content;
            }
        }

        self::assertStringContainsString('public string $f = "A\x07\x07\x08\x0C\x0B?\x00\n",', $content);
    }

    /**
     * @return list<CodeGeneratorResponse\File>
     * @throws ProtocException
     */
    private static function compile(Type $type, string $default): array
    {
        $request = new CodeGeneratorRequest(
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
                                    name: 'f',
                                    number: 1,
                                    label: FieldDescriptorProto\Label::LABEL_REQUIRED,
                                    type: $type,
                                    typeName: match ($type) {
                                        Type::TYPE_ENUM => '.t.E',
                                        Type::TYPE_MESSAGE => '.t.M',
                                        default => null,
                                    },
                                    defaultValue: $default,
                                    jsonName: 'f',
                                ),
                            ],
                        ),
                    ],
                    enumType: [
                        new EnumDescriptorProto(
                            name: 'E',
                            value: [new EnumValueDescriptorProto('class', 0), new EnumValueDescriptorProto('B', 1)],
                        ),
                    ],
                ),
            ],
        );

        return new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json')->compile($request);
    }
}
