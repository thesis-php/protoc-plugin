<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protoc\Plugin\Compiler;

#[CoversClass(Compiler::class)]
final class CompilerTest extends TestCase
{
    #[DataProvider('provideCompileCases')]
    public function testCompile(string $file, CodeGeneratorResponse $response): void
    {
        $hex = file_get_contents(__DIR__ . "/testdata/{$file}");
        self::assertIsString($hex);

        $bytes = hex2bin($hex);
        self::assertIsString($bytes);

        $serializer = new Serializer();
        $reflector = Reflector::build();

        $request = $reflector->map(
            $serializer->deserialize($reflector->type(CodeGeneratorRequest::class), $bytes),
            CodeGeneratorRequest::class,
        );

        self::assertEquals($response, new Compiler()->compile($request));
    }

    /**
     * @return iterable<array{non-empty-string, CodeGeneratorResponse}>
     */
    public static function provideCompileCases(): iterable
    {
        yield [
            'proto2/test.txt',
            new CodeGeneratorResponse(
                supportFeatures: Compiler::SUPPORTED_FEATURES,
                files: [
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/Foo.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

/**
 * @api
 *
 * Enum comment.
 */
enum Foo: int
{
    /** Case comment. */
    case FOO_UNSPECIFIED = 0;
    case FOO_BAR = 1;
    case FOO_BAZ = 2;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use BcMath\Number;
use Proto\Api\V1\TestRequest\Kind;
use Proto\Api\V1\TestRequest\Nested;
use Proto\Api\V1\TestRequest\Nested\Deep;
use Thesis\Protobuf;
use Thesis\Protobuf\Known;
use Thesis\Protobuf\Reflection;

/**
 * @api
 *
 * Comment on TestRequest message.
 */
final readonly class TestRequest
{
    /**
     * @param ?Kind $kind base field comment.
     * @param bool $boolRequired another field comment.
     * @param list<bool> $boolRepeated
     * @param list<int> $int32Repeated
     * @param list<Number> $int64Repeated
     * @param list<int> $fixed32Repeated
     * @param list<Number> $fixed64Repeated
     * @param list<int> $uint32Repeated
     * @param list<Number> $uint64Repeated
     * @param list<float> $floatRepeated
     * @param list<float> $doubleRepeated
     * @param list<string> $stringRepeated
     * @param list<string> $bytesRepeated
     * @param list<int> $sint32Repeated
     * @param list<Number> $sint64Repeated
     * @param list<int> $sfixed32Repeated
     * @param list<Number> $sfixed64Repeated
     * @param list<bool> $boolRepeatedPacked
     * @param list<int> $int32RepeatedPacked
     * @param list<Number> $int64RepeatedPacked
     * @param list<int> $fixed32RepeatedPacked
     * @param list<Number> $fixed64RepeatedPacked
     * @param list<int> $uint32RepeatedPacked
     * @param list<Number> $uint64RepeatedPacked
     * @param list<float> $floatRepeatedPacked
     * @param list<float> $doubleRepeatedPacked
     * @param list<int> $sint32RepeatedPacked
     * @param list<Number> $sint64RepeatedPacked
     * @param list<int> $sfixed32RepeatedPacked
     * @param list<Number> $sfixed64RepeatedPacked
     * @param Protobuf\Map<string, string> $mapStringString
     * @param ?string $lastField Maximum possible tag number.
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(Kind::class))]
        public ?Kind $kind = null,
        #[Reflection\Field(10, Reflection\BoolT::T)]
        public bool $boolRequired = false,
        #[Reflection\Field(11, Reflection\Int32T::T)]
        public int $int32Required = 0,
        #[Reflection\Field(12, Reflection\Int64T::T)]
        public Number $int64Required = new Number(0),
        #[Reflection\Field(13, Reflection\Fixed32T::T)]
        public int $fixed32Required = 0,
        #[Reflection\Field(14, Reflection\Fixed64T::T)]
        public Number $fixed64Required = new Number(0),
        #[Reflection\Field(15, Reflection\Uint32T::T)]
        public int $uint32Required = 0,
        #[Reflection\Field(16, Reflection\Uint64T::T)]
        public Number $uint64Required = new Number(0),
        #[Reflection\Field(17, Reflection\FloatT::T)]
        public float $gloatRequired = 0,
        #[Reflection\Field(18, Reflection\DoubleT::T)]
        public float $doubleRequired = 0,
        #[Reflection\Field(19, Reflection\StringT::T)]
        public string $stringRequired = '',
        #[Reflection\Field(101, Reflection\BytesT::T)]
        public string $bytesRequired = '',
        #[Reflection\Field(102, Reflection\SInt32T::T)]
        public int $sint32Required = 0,
        #[Reflection\Field(103, Reflection\SInt64T::T)]
        public Number $sint64Required = new Number(0),
        #[Reflection\Field(104, Reflection\SFixed32T::T)]
        public int $sfixed32Required = 0,
        #[Reflection\Field(105, Reflection\SFixed64T::T)]
        public Number $sfixed64Required = new Number(0),
        #[Reflection\Field(30, Reflection\BoolT::T)]
        public ?bool $boolOptional = null,
        #[Reflection\Field(31, Reflection\Int32T::T)]
        public ?int $int32Optional = null,
        #[Reflection\Field(32, Reflection\Int64T::T)]
        public ?Number $int64Optional = null,
        #[Reflection\Field(33, Reflection\Fixed32T::T)]
        public ?int $fixed32Optional = null,
        #[Reflection\Field(34, Reflection\Fixed64T::T)]
        public ?Number $fixed64Optional = null,
        #[Reflection\Field(35, Reflection\Uint32T::T)]
        public ?int $uint32Optional = null,
        #[Reflection\Field(36, Reflection\Uint64T::T)]
        public ?Number $uint64Optional = null,
        #[Reflection\Field(37, Reflection\FloatT::T)]
        public ?float $floatOptional = null,
        #[Reflection\Field(38, Reflection\DoubleT::T)]
        public ?float $doubleOptional = null,
        #[Reflection\Field(39, Reflection\StringT::T)]
        public ?string $stringOptional = null,
        #[Reflection\Field(301, Reflection\BytesT::T)]
        public ?string $bytesOptional = null,
        #[Reflection\Field(302, Reflection\SInt32T::T)]
        public ?int $sint32Optional = null,
        #[Reflection\Field(303, Reflection\SInt64T::T)]
        public ?Number $sint64Optional = null,
        #[Reflection\Field(304, Reflection\SFixed32T::T)]
        public ?int $sfixed32Optional = null,
        #[Reflection\Field(305, Reflection\SFixed64T::T)]
        public ?Number $sfixed64Optional = null,
        #[Reflection\Field(20, new Reflection\ListT(Reflection\BoolT::T, false))]
        public array $boolRepeated = [],
        #[Reflection\Field(21, new Reflection\ListT(Reflection\Int32T::T, false))]
        public array $int32Repeated = [],
        #[Reflection\Field(22, new Reflection\ListT(Reflection\Int64T::T, false))]
        public array $int64Repeated = [],
        #[Reflection\Field(23, new Reflection\ListT(Reflection\Fixed32T::T, false))]
        public array $fixed32Repeated = [],
        #[Reflection\Field(24, new Reflection\ListT(Reflection\Fixed64T::T, false))]
        public array $fixed64Repeated = [],
        #[Reflection\Field(25, new Reflection\ListT(Reflection\Uint32T::T, false))]
        public array $uint32Repeated = [],
        #[Reflection\Field(26, new Reflection\ListT(Reflection\Uint64T::T, false))]
        public array $uint64Repeated = [],
        #[Reflection\Field(27, new Reflection\ListT(Reflection\FloatT::T, false))]
        public array $floatRepeated = [],
        #[Reflection\Field(28, new Reflection\ListT(Reflection\DoubleT::T, false))]
        public array $doubleRepeated = [],
        #[Reflection\Field(29, new Reflection\ListT(Reflection\StringT::T, false))]
        public array $stringRepeated = [],
        #[Reflection\Field(201, new Reflection\ListT(Reflection\BytesT::T, false))]
        public array $bytesRepeated = [],
        #[Reflection\Field(202, new Reflection\ListT(Reflection\SInt32T::T, false))]
        public array $sint32Repeated = [],
        #[Reflection\Field(203, new Reflection\ListT(Reflection\SInt64T::T, false))]
        public array $sint64Repeated = [],
        #[Reflection\Field(204, new Reflection\ListT(Reflection\SFixed32T::T, false))]
        public array $sfixed32Repeated = [],
        #[Reflection\Field(205, new Reflection\ListT(Reflection\SFixed64T::T, false))]
        public array $sfixed64Repeated = [],
        #[Reflection\Field(50, new Reflection\ListT(Reflection\BoolT::T, true))]
        public array $boolRepeatedPacked = [],
        #[Reflection\Field(51, new Reflection\ListT(Reflection\Int32T::T, true))]
        public array $int32RepeatedPacked = [],
        #[Reflection\Field(52, new Reflection\ListT(Reflection\Int64T::T, true))]
        public array $int64RepeatedPacked = [],
        #[Reflection\Field(53, new Reflection\ListT(Reflection\Fixed32T::T, true))]
        public array $fixed32RepeatedPacked = [],
        #[Reflection\Field(54, new Reflection\ListT(Reflection\Fixed64T::T, true))]
        public array $fixed64RepeatedPacked = [],
        #[Reflection\Field(55, new Reflection\ListT(Reflection\Uint32T::T, true))]
        public array $uint32RepeatedPacked = [],
        #[Reflection\Field(56, new Reflection\ListT(Reflection\Uint64T::T, true))]
        public array $uint64RepeatedPacked = [],
        #[Reflection\Field(57, new Reflection\ListT(Reflection\FloatT::T, true))]
        public array $floatRepeatedPacked = [],
        #[Reflection\Field(58, new Reflection\ListT(Reflection\DoubleT::T, true))]
        public array $doubleRepeatedPacked = [],
        #[Reflection\Field(502, new Reflection\ListT(Reflection\SInt32T::T, true))]
        public array $sint32RepeatedPacked = [],
        #[Reflection\Field(503, new Reflection\ListT(Reflection\SInt64T::T, true))]
        public array $sint64RepeatedPacked = [],
        #[Reflection\Field(504, new Reflection\ListT(Reflection\SFixed32T::T, true))]
        public array $sfixed32RepeatedPacked = [],
        #[Reflection\Field(505, new Reflection\ListT(Reflection\SFixed64T::T, true))]
        public array $sfixed64RepeatedPacked = [],
        #[Reflection\Field(40, Reflection\BoolT::T)]
        public ?bool $boolDefaulted = true,
        #[Reflection\Field(41, Reflection\Int32T::T)]
        public ?int $int32Defaulted = 32,
        #[Reflection\Field(42, Reflection\Int64T::T)]
        public ?Number $int64Defaulted = new Number(64),
        #[Reflection\Field(43, Reflection\Fixed32T::T)]
        public ?int $fixed32Defaulted = 320,
        #[Reflection\Field(44, Reflection\Fixed64T::T)]
        public ?Number $fixed64Defaulted = new Number(640),
        #[Reflection\Field(45, Reflection\Uint32T::T)]
        public ?int $uint32Defaulted = 3200,
        #[Reflection\Field(46, Reflection\Uint64T::T)]
        public ?Number $uint64Defaulted = new Number(6400),
        #[Reflection\Field(47, Reflection\FloatT::T)]
        public ?float $floatDefaulted = 314159.0,
        #[Reflection\Field(48, Reflection\DoubleT::T)]
        public ?float $doubleDefaulted = 271828.0,
        #[Reflection\Field(49, Reflection\StringT::T)]
        public ?string $stringDefaulted = "hello, \"world!\"\n",
        #[Reflection\Field(401, Reflection\BytesT::T)]
        public ?string $bytesDefaulted = 'Bignose',
        #[Reflection\Field(402, Reflection\SInt32T::T)]
        public ?int $sint32Defaulted = -32,
        #[Reflection\Field(403, Reflection\SInt64T::T)]
        public ?Number $sint64Defaulted = new Number(-64),
        #[Reflection\Field(404, Reflection\SFixed32T::T)]
        public ?int $sfixed32Defaulted = -32,
        #[Reflection\Field(405, Reflection\SFixed64T::T)]
        public ?Number $sfixed64Defaulted = new Number(-64),
        #[Reflection\Field(406, new Reflection\MapT(Reflection\StringT::T, Reflection\StringT::T))]
        public Protobuf\Map $mapStringString = new Protobuf\Map(),
        #[Reflection\Field(407, new Reflection\ObjectT(Known\Timestamp::class))]
        public ?Known\Timestamp $knownTimestamp = null,
        #[Reflection\Field(408, new Reflection\ObjectT(Known\Duration::class))]
        public ?Known\Duration $knownDuration = null,
        #[Reflection\Field(409, new Reflection\ObjectT(Known\Struct::class))]
        public ?Known\Struct $knownStruct = null,
        #[Reflection\Field(410, new Reflection\ObjectT(Known\EmptyObject::class))]
        public ?Known\EmptyObject $knownEmpty = null,
        #[Reflection\Field(411, new Reflection\ObjectT(Known\Any::class))]
        public ?Known\Any $knownAny = null,
        #[Reflection\Field(412, new Reflection\ObjectT(Nested::class))]
        public ?Nested $nested = null,
        #[Reflection\Field(413, new Reflection\ObjectT(Deep::class))]
        public ?Deep $nestedDeep = null,
        #[Reflection\Field(536870911, Reflection\StringT::T)]
        public ?string $lastField = null,
        #[Reflection\OneOf([
            TestRequest\XFieldNumber::class,
            TestRequest\XFieldName::class,
            TestRequest\XFieldData::class,
            TestRequest\XFieldTempC::class,
            TestRequest\XFieldCol::class,
            TestRequest\XFieldDeepEnum::class,
        ])]
        public ?TestRequest\XField $xField = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldNumber.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldNumber implements XField
{
    public function __construct(
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public ?int $number = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldName.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldName implements XField
{
    public function __construct(
        #[Reflection\Field(6, Reflection\StringT::T)]
        public ?string $name = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldData.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldData implements XField
{
    public function __construct(
        #[Reflection\Field(7, Reflection\BytesT::T)]
        public ?string $data = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldTempC.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldTempC implements XField
{
    public function __construct(
        #[Reflection\Field(8, Reflection\DoubleT::T)]
        public ?float $tempC = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldCol.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldCol implements XField
{
    public function __construct(
        #[Reflection\Field(9, new Reflection\ObjectT(Nested::class))]
        public ?Nested $col = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldDeepEnum.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldDeepEnum implements XField
{
    public function __construct(
        #[Reflection\Field(4, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XField.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

/**
 * @api
 * @phpstan-sealed (
 *   XFieldNumber |
 *   XFieldName |
 *   XFieldData |
 *   XFieldTempC |
 *   XFieldCol |
 *   XFieldDeepEnum
 * )
 */
interface XField
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Kind.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

/**
 * @api
 *
 * Nested enum comment.
 */
enum Kind: int
{
    /** Nested enum comment case. */
    case KIND_UNSPECIFIED = 0;
    case VOID = 1;
    case STRING = 2;
    case BOOL = 3;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Proto\Api\V1\TestRequest\Nested\Deep;
use Thesis\Protobuf\Reflection;

/**
 * @api
 *
 * Comment on nested message.
 */
final readonly class Nested
{
    /**
     * @param ?string $name Comment on nested message field.
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $group = null,
        #[Reflection\Field(3, new Reflection\ObjectT(Deep::class))]
        public ?Deep $deep = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested/Deep.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\Nested;

use Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Deep
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(4, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
        #[Reflection\OneOf([Deep\UnionPhone::class, Deep\UnionEmail::class])]
        public ?Deep\Union $union = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested/Deep/UnionPhone.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\Nested\Deep;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UnionPhone implements Union
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $phone = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested/Deep/UnionEmail.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\Nested\Deep;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UnionEmail implements Union
{
    public function __construct(
        #[Reflection\Field(3, Reflection\StringT::T)]
        public ?string $email = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested/Deep/Union.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\Nested\Deep;

/**
 * @api
 * @phpstan-sealed (
 *   UnionPhone |
 *   UnionEmail
 * )
 */
interface Union
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Nested/Deep/DeepEnum.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\Nested\Deep;

/**
 * @api
 */
enum DeepEnum: int
{
    case DEEP_ENUM_UNSPECIFIED = 0;
    case DEEP_ENUM_FOO = 1;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/AnotherRequest.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Proto\Api\V1\TestRequest\Kind;
use Proto\Api\V1\TestRequest\Nested;
use Proto\Api\V1\TestRequest\Nested\Deep;
use Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class AnotherRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Nested::class))]
        public ?Nested $nested = null,
        #[Reflection\Field(2, new Reflection\ObjectT(Deep::class))]
        public ?Deep $deep = null,
        #[Reflection\Field(3, new Reflection\EnumT(Kind::class))]
        public ?Kind $kind = null,
        #[Reflection\Field(4, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'php_namespace/php_namespace.txt',
            new CodeGeneratorResponse(
                supportFeatures: Compiler::SUPPORTED_FEATURES,
                files: [
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Api/V1/TestRequest.php',
                        content: self::phpContent(
                            source: 'php_namespace/php_namespace.proto',
                            content: <<<'PHP'
namespace Thesis\Api\V1;

/**
 * @api
 */
final readonly class TestRequest
{
}

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'snake_case/snake_case.txt',
            new CodeGeneratorResponse(
                supportFeatures: Compiler::SUPPORTED_FEATURES,
                files: [
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/Foo.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

/**
 * @api
 */
enum Foo: int
{
    case FOO_UNSPECIFIED = 0;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Proto\Api\V1\TestRequest\NestedMessage;
use Proto\Api\V1\TestRequest\NestedMessage\Deep;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class TestRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(NestedMessage::class))]
        public ?NestedMessage $nested = null,
        #[Reflection\Field(5, new Reflection\ObjectT(Deep::class))]
        public ?Deep $nestedDeep = null,
        #[Reflection\OneOf([TestRequest\XFieldNumber::class, TestRequest\XFieldCol::class, TestRequest\XFieldDeepEnum::class])]
        public ?TestRequest\XField $xField = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldNumber.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldNumber implements XField
{
    public function __construct(
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $number = 0,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldCol.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldCol implements XField
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\ObjectT(NestedMessage::class))]
        public ?NestedMessage $col = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XFieldDeepEnum.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldDeepEnum implements XField
{
    public function __construct(
        #[Reflection\Field(4, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/XField.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

/**
 * @api
 * @phpstan-sealed (
 *   XFieldNumber |
 *   XFieldCol |
 *   XFieldDeepEnum
 * )
 */
interface XField
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Proto\Api\V1\TestRequest\NestedMessage\Deep;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class NestedMessage
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Deep::class))]
        public ?Deep $deep = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage/Deep.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\NestedMessage;

use Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Deep
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
        #[Reflection\OneOf([Deep\UnionPhone::class, Deep\UnionEmail::class])]
        public ?Deep\Union $union = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage/Deep/UnionPhone.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\NestedMessage\Deep;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UnionPhone implements Union
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $phone = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage/Deep/UnionEmail.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\NestedMessage\Deep;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class UnionEmail implements Union
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $email = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage/Deep/Union.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\NestedMessage\Deep;

/**
 * @api
 * @phpstan-sealed (
 *   UnionPhone |
 *   UnionEmail
 * )
 */
interface Union
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage/Deep/DeepEnum.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest\NestedMessage\Deep;

/**
 * @api
 */
enum DeepEnum: int
{
    case DEEP_ENUM_UNSPECIFIED = 0;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/AnotherRequest.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Proto\Api\V1\TestRequest\NestedMessage;
use Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class AnotherRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(NestedMessage::class))]
        public ?NestedMessage $nested = null,
        #[Reflection\Field(2, new Reflection\EnumT(DeepEnum::class))]
        public ?DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'reserved_names/reserved_names.txt',
            new CodeGeneratorResponse(
                supportFeatures: Compiler::SUPPORTED_FEATURES,
                files: [
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/NotAllowed.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

/**
 * @api
 */
enum NotAllowed: int
{
    case abstract = 0;
    case and = 1;
    case array = 2;
    case empty = 3;
    case class_ = 4;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/String_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

/**
 * @api
 */
enum String_: int
{
    case ZERO1 = 0;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/Trait_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

/**
 * @api
 */
enum Trait_: int
{
    case ZERO2 = 0;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/Exit_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

/**
 * @api
 */
final readonly class Exit_
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/INSTANCEOF_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

use ReservedTypes\Class_\Case_\Break_;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class INSTANCEOF_
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Break_::class))]
        public ?Break_ $break = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/Class_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

use ReservedTypes\Class_\Case_\Break_;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Class_
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Break_::class))]
        public ?Break_ $break = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/Class_/Case_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes\Class_;

/**
 * @api
 */
final readonly class Case_
{
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/Class_/Case_/Break_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes\Class_\Case_;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Break_
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'proto3/test.txt',
            new CodeGeneratorResponse(
                supportFeatures: Compiler::SUPPORTED_FEATURES,
                files: [
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest.php',
                        content: self::phpContent(
                            'proto3/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class TestRequest
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $stringValue = '',
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $optionalStringValue = null,
        #[Reflection\OneOf([TestRequest\ContactPhone::class, TestRequest\ContactEmail::class])]
        public ?TestRequest\Contact $contact = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/ContactPhone.php',
                        content: self::phpContent(
                            'proto3/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ContactPhone implements Contact
{
    public function __construct(
        #[Reflection\Field(3, Reflection\StringT::T)]
        public string $phone = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/ContactEmail.php',
                        content: self::phpContent(
                            'proto3/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class ContactEmail implements Contact
{
    public function __construct(
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $email = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/Contact.php',
                        content: self::phpContent(
                            'proto3/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

/**
 * @api
 * @phpstan-sealed (
 *   ContactPhone |
 *   ContactEmail
 * )
 */
interface Contact
{
}

PHP,
                        ),
                    ),
                ],
            ),
        ];
    }

    private static function phpContent(string $source, string $content): string
    {
        return \sprintf(
            <<<'PHP'
<?php

/**
 * Code generated by thesis/protoc-plugin. DO NOT EDIT.
 * Versions:
 *   thesis/protoc-plugin — v%s
 *   protoc               — v6.32.1
 * Source: %s
 */

declare(strict_types=1);

%s
PHP,
            Compiler::PLUGIN_VERSION,
            $source,
            $content,
        );
    }
}
