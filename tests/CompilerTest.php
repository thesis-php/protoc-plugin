<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use BcMath\Number;
use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Package;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
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

        $encoder = Encoder\Builder::buildDefault();
        $decoder = Decoder\Builder::buildDefault();

        $request = $decoder->decode($bytes, CodeGeneratorRequest::class);

        self::assertEquals($response, new Compiler($encoder)->compile($request));
    }

    /**
     * @return iterable<array{non-empty-string, CodeGeneratorResponse}>
     */
    public static function provideCompileCases(): iterable
    {
        yield [
            'proto2/test.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
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

use Thesis\Protobuf;
use Thesis\Protobuf\Reflection;

/**
 * @api
 *
 * Comment on TestRequest message.
 */
final readonly class TestRequest
{
    /**
     * @param ?\Proto\Api\V1\TestRequest\Kind $kind base field comment.
     * @param bool $boolRequired another field comment.
     * @param list<bool> $boolRepeated
     * @param list<int> $int32Repeated
     * @param list<\BcMath\Number> $int64Repeated
     * @param list<int> $fixed32Repeated
     * @param list<\BcMath\Number> $fixed64Repeated
     * @param list<int> $uint32Repeated
     * @param list<\BcMath\Number> $uint64Repeated
     * @param list<float> $floatRepeated
     * @param list<float> $doubleRepeated
     * @param list<string> $stringRepeated
     * @param list<string> $bytesRepeated
     * @param list<int> $sint32Repeated
     * @param list<\BcMath\Number> $sint64Repeated
     * @param list<int> $sfixed32Repeated
     * @param list<\BcMath\Number> $sfixed64Repeated
     * @param list<bool> $boolRepeatedPacked
     * @param list<int> $int32RepeatedPacked
     * @param list<\BcMath\Number> $int64RepeatedPacked
     * @param list<int> $fixed32RepeatedPacked
     * @param list<\BcMath\Number> $fixed64RepeatedPacked
     * @param list<int> $uint32RepeatedPacked
     * @param list<\BcMath\Number> $uint64RepeatedPacked
     * @param list<float> $floatRepeatedPacked
     * @param list<float> $doubleRepeatedPacked
     * @param list<int> $sint32RepeatedPacked
     * @param list<\BcMath\Number> $sint64RepeatedPacked
     * @param list<int> $sfixed32RepeatedPacked
     * @param list<\BcMath\Number> $sfixed64RepeatedPacked
     * @param Protobuf\Map<string, string> $mapStringString
     * @param ?string $lastField Maximum possible tag number.
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(\Proto\Api\V1\TestRequest\Kind::class))]
        public ?\Proto\Api\V1\TestRequest\Kind $kind = null,
        #[Reflection\Field(10, Reflection\BoolT::T)]
        public bool $boolRequired = false,
        #[Reflection\Field(11, Reflection\Int32T::T)]
        public int $int32Required = 0,
        #[Reflection\Field(12, Reflection\Int64T::T)]
        public \BcMath\Number $int64Required = new \BcMath\Number(0),
        #[Reflection\Field(13, Reflection\Fixed32T::T)]
        public int $fixed32Required = 0,
        #[Reflection\Field(14, Reflection\Fixed64T::T)]
        public \BcMath\Number $fixed64Required = new \BcMath\Number(0),
        #[Reflection\Field(15, Reflection\Uint32T::T)]
        public int $uint32Required = 0,
        #[Reflection\Field(16, Reflection\Uint64T::T)]
        public \BcMath\Number $uint64Required = new \BcMath\Number(0),
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
        public \BcMath\Number $sint64Required = new \BcMath\Number(0),
        #[Reflection\Field(104, Reflection\SFixed32T::T)]
        public int $sfixed32Required = 0,
        #[Reflection\Field(105, Reflection\SFixed64T::T)]
        public \BcMath\Number $sfixed64Required = new \BcMath\Number(0),
        #[Reflection\Field(30, Reflection\BoolT::T)]
        public ?bool $boolOptional = null,
        #[Reflection\Field(31, Reflection\Int32T::T)]
        public ?int $int32Optional = null,
        #[Reflection\Field(32, Reflection\Int64T::T)]
        public ?\BcMath\Number $int64Optional = null,
        #[Reflection\Field(33, Reflection\Fixed32T::T)]
        public ?int $fixed32Optional = null,
        #[Reflection\Field(34, Reflection\Fixed64T::T)]
        public ?\BcMath\Number $fixed64Optional = null,
        #[Reflection\Field(35, Reflection\Uint32T::T)]
        public ?int $uint32Optional = null,
        #[Reflection\Field(36, Reflection\Uint64T::T)]
        public ?\BcMath\Number $uint64Optional = null,
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
        public ?\BcMath\Number $sint64Optional = null,
        #[Reflection\Field(304, Reflection\SFixed32T::T)]
        public ?int $sfixed32Optional = null,
        #[Reflection\Field(305, Reflection\SFixed64T::T)]
        public ?\BcMath\Number $sfixed64Optional = null,
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
        public ?\BcMath\Number $int64Defaulted = new \BcMath\Number(64),
        #[Reflection\Field(43, Reflection\Fixed32T::T)]
        public ?int $fixed32Defaulted = 320,
        #[Reflection\Field(44, Reflection\Fixed64T::T)]
        public ?\BcMath\Number $fixed64Defaulted = new \BcMath\Number(640),
        #[Reflection\Field(45, Reflection\Uint32T::T)]
        public ?int $uint32Defaulted = 3200,
        #[Reflection\Field(46, Reflection\Uint64T::T)]
        public ?\BcMath\Number $uint64Defaulted = new \BcMath\Number(6400),
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
        public ?\BcMath\Number $sint64Defaulted = new \BcMath\Number(-64),
        #[Reflection\Field(404, Reflection\SFixed32T::T)]
        public ?int $sfixed32Defaulted = -32,
        #[Reflection\Field(405, Reflection\SFixed64T::T)]
        public ?\BcMath\Number $sfixed64Defaulted = new \BcMath\Number(-64),
        #[Reflection\Field(406, new Reflection\MapT(Reflection\StringT::T, Reflection\StringT::T))]
        public Protobuf\Map $mapStringString = new Protobuf\Map(),
        #[Reflection\Field(407, new Reflection\ObjectT(\Google\Protobuf\Timestamp::class))]
        public ?\Google\Protobuf\Timestamp $knownTimestamp = null,
        #[Reflection\Field(408, new Reflection\ObjectT(\Google\Protobuf\Duration::class))]
        public ?\Google\Protobuf\Duration $knownDuration = null,
        #[Reflection\Field(409, new Reflection\ObjectT(\Google\Protobuf\Struct::class))]
        public ?\Google\Protobuf\Struct $knownStruct = null,
        #[Reflection\Field(410, new Reflection\ObjectT(\Google\Protobuf\Empty_::class))]
        public ?\Google\Protobuf\Empty_ $knownEmpty = null,
        #[Reflection\Field(411, new Reflection\ObjectT(\Google\Protobuf\Any::class))]
        public ?\Google\Protobuf\Any $knownAny = null,
        #[Reflection\Field(412, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested::class))]
        public ?\Proto\Api\V1\TestRequest\Nested $nested = null,
        #[Reflection\Field(413, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested\Deep::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep $nestedDeep = null,
        #[Reflection\Field(536870911, Reflection\StringT::T)]
        public ?string $lastField = null,
        #[Reflection\OneOf([
            \Proto\Api\V1\TestRequest\XFieldNumber::class,
            \Proto\Api\V1\TestRequest\XFieldName::class,
            \Proto\Api\V1\TestRequest\XFieldData::class,
            \Proto\Api\V1\TestRequest\XFieldTempC::class,
            \Proto\Api\V1\TestRequest\XFieldCol::class,
            \Proto\Api\V1\TestRequest\XFieldDeepEnum::class,
        ])]
        public ?\Proto\Api\V1\TestRequest\XField $xField = null,
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
final readonly class XFieldNumber implements \Proto\Api\V1\TestRequest\XField
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
final readonly class XFieldName implements \Proto\Api\V1\TestRequest\XField
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
final readonly class XFieldData implements \Proto\Api\V1\TestRequest\XField
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
final readonly class XFieldTempC implements \Proto\Api\V1\TestRequest\XField
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
final readonly class XFieldCol implements \Proto\Api\V1\TestRequest\XField
{
    public function __construct(
        #[Reflection\Field(9, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested::class))]
        public ?\Proto\Api\V1\TestRequest\Nested $col = null,
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldDeepEnum implements \Proto\Api\V1\TestRequest\XField
{
    public function __construct(
        #[Reflection\Field(4, new Reflection\EnumT(\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum $deepEnum = null,
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
interface XField {}

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
        #[Reflection\Field(3, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested\Deep::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep $deep = null,
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Deep
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(4, new Reflection\EnumT(\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum $deepEnum = null,
        #[Reflection\OneOf([\Proto\Api\V1\TestRequest\Nested\Deep\UnionPhone::class, \Proto\Api\V1\TestRequest\Nested\Deep\UnionEmail::class])]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep\Union $union = null,
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
final readonly class UnionPhone implements \Proto\Api\V1\TestRequest\Nested\Deep\Union
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
final readonly class UnionEmail implements \Proto\Api\V1\TestRequest\Nested\Deep\Union
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
interface Union {}

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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class AnotherRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested::class))]
        public ?\Proto\Api\V1\TestRequest\Nested $nested = null,
        #[Reflection\Field(2, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\Nested\Deep::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep $deep = null,
        #[Reflection\Field(3, new Reflection\EnumT(\Proto\Api\V1\TestRequest\Kind::class))]
        public ?\Proto\Api\V1\TestRequest\Kind $kind = null,
        #[Reflection\Field(4, new Reflection\EnumT(\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/Proto2TestDescriptorRegistry.php',
                        content: self::phpContent(
                            'proto2/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class Proto2TestDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'ChFwcm90bzIvdGVzdC5wcm90bxIMcHJvdG8uYXBpLnYxGh9nb29nbGUvcHJvdG9idWYvdGltZXN0YW1wLnByb3RvGh5nb29nbGUvcHJvdG9idWYvZHVyYXRpb24ucHJvdG8aHGdvb2dsZS9wcm90b2J1Zi9zdHJ1Y3QucHJvdG8aG2dvb2dsZS9wcm90b2J1Zi9lbXB0eS5wcm90bxoZZ29vZ2xlL3Byb3RvYnVmL2FueS5wcm90byLHJAoLVGVzdFJlcXVlc3QSMgoEa2luZBgBIAIoDjIeLnByb3RvLmFwaS52MS5UZXN0UmVxdWVzdC5LaW5kUgRraW5kEiMKDWJvb2xfcmVxdWlyZWQYCiACKAhSDGJvb2xSZXF1aXJlZBIlCg5pbnQzMl9yZXF1aXJlZBgLIAIoBVINaW50MzJSZXF1aXJlZBIlCg5pbnQ2NF9yZXF1aXJlZBgMIAIoA1INaW50NjRSZXF1aXJlZBIpChBmaXhlZDMyX3JlcXVpcmVkGA0gAigHUg9maXhlZDMyUmVxdWlyZWQSKQoQZml4ZWQ2NF9yZXF1aXJlZBgOIAIoBlIPZml4ZWQ2NFJlcXVpcmVkEicKD3VpbnQzMl9yZXF1aXJlZBgPIAIoDVIOdWludDMyUmVxdWlyZWQSJwoPdWludDY0X3JlcXVpcmVkGBAgAigEUg51aW50NjRSZXF1aXJlZBIlCg5nbG9hdF9yZXF1aXJlZBgRIAIoAlINZ2xvYXRSZXF1aXJlZBInCg9kb3VibGVfcmVxdWlyZWQYEiACKAFSDmRvdWJsZVJlcXVpcmVkEicKD3N0cmluZ19yZXF1aXJlZBgTIAIoCVIOc3RyaW5nUmVxdWlyZWQSJQoOYnl0ZXNfcmVxdWlyZWQYZSACKAxSDWJ5dGVzUmVxdWlyZWQSJwoPc2ludDMyX3JlcXVpcmVkGGYgAigRUg5zaW50MzJSZXF1aXJlZBInCg9zaW50NjRfcmVxdWlyZWQYZyACKBJSDnNpbnQ2NFJlcXVpcmVkEisKEXNmaXhlZDMyX3JlcXVpcmVkGGggAigPUhBzZml4ZWQzMlJlcXVpcmVkEisKEXNmaXhlZDY0X3JlcXVpcmVkGGkgAigQUhBzZml4ZWQ2NFJlcXVpcmVkEiMKDWJvb2xfb3B0aW9uYWwYHiABKAhSDGJvb2xPcHRpb25hbBIlCg5pbnQzMl9vcHRpb25hbBgfIAEoBVINaW50MzJPcHRpb25hbBIlCg5pbnQ2NF9vcHRpb25hbBggIAEoA1INaW50NjRPcHRpb25hbBIpChBmaXhlZDMyX29wdGlvbmFsGCEgASgHUg9maXhlZDMyT3B0aW9uYWwSKQoQZml4ZWQ2NF9vcHRpb25hbBgiIAEoBlIPZml4ZWQ2NE9wdGlvbmFsEicKD3VpbnQzMl9vcHRpb25hbBgjIAEoDVIOdWludDMyT3B0aW9uYWwSJwoPdWludDY0X29wdGlvbmFsGCQgASgEUg51aW50NjRPcHRpb25hbBIlCg5mbG9hdF9vcHRpb25hbBglIAEoAlINZmxvYXRPcHRpb25hbBInCg9kb3VibGVfb3B0aW9uYWwYJiABKAFSDmRvdWJsZU9wdGlvbmFsEicKD3N0cmluZ19vcHRpb25hbBgnIAEoCVIOc3RyaW5nT3B0aW9uYWwSJgoOYnl0ZXNfb3B0aW9uYWwYrQIgASgMUg1ieXRlc09wdGlvbmFsEigKD3NpbnQzMl9vcHRpb25hbBiuAiABKBFSDnNpbnQzMk9wdGlvbmFsEigKD3NpbnQ2NF9vcHRpb25hbBivAiABKBJSDnNpbnQ2NE9wdGlvbmFsEiwKEXNmaXhlZDMyX29wdGlvbmFsGLACIAEoD1IQc2ZpeGVkMzJPcHRpb25hbBIsChFzZml4ZWQ2NF9vcHRpb25hbBixAiABKBBSEHNmaXhlZDY0T3B0aW9uYWwSIwoNYm9vbF9yZXBlYXRlZBgUIAMoCFIMYm9vbFJlcGVhdGVkEiUKDmludDMyX3JlcGVhdGVkGBUgAygFUg1pbnQzMlJlcGVhdGVkEiUKDmludDY0X3JlcGVhdGVkGBYgAygDUg1pbnQ2NFJlcGVhdGVkEikKEGZpeGVkMzJfcmVwZWF0ZWQYFyADKAdSD2ZpeGVkMzJSZXBlYXRlZBIpChBmaXhlZDY0X3JlcGVhdGVkGBggAygGUg9maXhlZDY0UmVwZWF0ZWQSJwoPdWludDMyX3JlcGVhdGVkGBkgAygNUg51aW50MzJSZXBlYXRlZBInCg91aW50NjRfcmVwZWF0ZWQYGiADKARSDnVpbnQ2NFJlcGVhdGVkEiUKDmZsb2F0X3JlcGVhdGVkGBsgAygCUg1mbG9hdFJlcGVhdGVkEicKD2RvdWJsZV9yZXBlYXRlZBgcIAMoAVIOZG91YmxlUmVwZWF0ZWQSJwoPc3RyaW5nX3JlcGVhdGVkGB0gAygJUg5zdHJpbmdSZXBlYXRlZBImCg5ieXRlc19yZXBlYXRlZBjJASADKAxSDWJ5dGVzUmVwZWF0ZWQSKAoPc2ludDMyX3JlcGVhdGVkGMoBIAMoEVIOc2ludDMyUmVwZWF0ZWQSKAoPc2ludDY0X3JlcGVhdGVkGMsBIAMoElIOc2ludDY0UmVwZWF0ZWQSLAoRc2ZpeGVkMzJfcmVwZWF0ZWQYzAEgAygPUhBzZml4ZWQzMlJlcGVhdGVkEiwKEXNmaXhlZDY0X3JlcGVhdGVkGM0BIAMoEFIQc2ZpeGVkNjRSZXBlYXRlZBI0ChRib29sX3JlcGVhdGVkX3BhY2tlZBgyIAMoCFISYm9vbFJlcGVhdGVkUGFja2VkQgIQARI2ChVpbnQzMl9yZXBlYXRlZF9wYWNrZWQYMyADKAVSE2ludDMyUmVwZWF0ZWRQYWNrZWRCAhABEjYKFWludDY0X3JlcGVhdGVkX3BhY2tlZBg0IAMoA1ITaW50NjRSZXBlYXRlZFBhY2tlZEICEAESOgoXZml4ZWQzMl9yZXBlYXRlZF9wYWNrZWQYNSADKAdSFWZpeGVkMzJSZXBlYXRlZFBhY2tlZEICEAESOgoXZml4ZWQ2NF9yZXBlYXRlZF9wYWNrZWQYNiADKAZSFWZpeGVkNjRSZXBlYXRlZFBhY2tlZEICEAESOAoWdWludDMyX3JlcGVhdGVkX3BhY2tlZBg3IAMoDVIUdWludDMyUmVwZWF0ZWRQYWNrZWRCAhABEjgKFnVpbnQ2NF9yZXBlYXRlZF9wYWNrZWQYOCADKARSFHVpbnQ2NFJlcGVhdGVkUGFja2VkQgIQARI2ChVmbG9hdF9yZXBlYXRlZF9wYWNrZWQYOSADKAJSE2Zsb2F0UmVwZWF0ZWRQYWNrZWRCAhABEjgKFmRvdWJsZV9yZXBlYXRlZF9wYWNrZWQYOiADKAFSFGRvdWJsZVJlcGVhdGVkUGFja2VkQgIQARI5ChZzaW50MzJfcmVwZWF0ZWRfcGFja2VkGPYDIAMoEVIUc2ludDMyUmVwZWF0ZWRQYWNrZWRCAhABEjkKFnNpbnQ2NF9yZXBlYXRlZF9wYWNrZWQY9wMgAygSUhRzaW50NjRSZXBlYXRlZFBhY2tlZEICEAESPQoYc2ZpeGVkMzJfcmVwZWF0ZWRfcGFja2VkGPgDIAMoD1IWc2ZpeGVkMzJSZXBlYXRlZFBhY2tlZEICEAESPQoYc2ZpeGVkNjRfcmVwZWF0ZWRfcGFja2VkGPkDIAMoEFIWc2ZpeGVkNjRSZXBlYXRlZFBhY2tlZEICEAESKwoOYm9vbF9kZWZhdWx0ZWQYKCABKAg6BHRydWVSDWJvb2xEZWZhdWx0ZWQSKwoPaW50MzJfZGVmYXVsdGVkGCkgASgFOgIzMlIOaW50MzJEZWZhdWx0ZWQSKwoPaW50NjRfZGVmYXVsdGVkGCogASgDOgI2NFIOaW50NjREZWZhdWx0ZWQSMAoRZml4ZWQzMl9kZWZhdWx0ZWQYKyABKAc6AzMyMFIQZml4ZWQzMkRlZmF1bHRlZBIwChFmaXhlZDY0X2RlZmF1bHRlZBgsIAEoBjoDNjQwUhBmaXhlZDY0RGVmYXVsdGVkEi8KEHVpbnQzMl9kZWZhdWx0ZWQYLSABKA06BDMyMDBSD3VpbnQzMkRlZmF1bHRlZBIvChB1aW50NjRfZGVmYXVsdGVkGC4gASgEOgQ2NDAwUg91aW50NjREZWZhdWx0ZWQSLwoPZmxvYXRfZGVmYXVsdGVkGC8gASgCOgYzMTQxNTlSDmZsb2F0RGVmYXVsdGVkEjEKEGRvdWJsZV9kZWZhdWx0ZWQYMCABKAE6BjI3MTgyOFIPZG91YmxlRGVmYXVsdGVkEjsKEHN0cmluZ19kZWZhdWx0ZWQYMSABKAk6EGhlbGxvLCAid29ybGQhIgpSD3N0cmluZ0RlZmF1bHRlZBIxCg9ieXRlc19kZWZhdWx0ZWQYkQMgASgMOgdCaWdub3NlUg5ieXRlc0RlZmF1bHRlZBIvChBzaW50MzJfZGVmYXVsdGVkGJIDIAEoEToDLTMyUg9zaW50MzJEZWZhdWx0ZWQSLwoQc2ludDY0X2RlZmF1bHRlZBiTAyABKBI6Ay02NFIPc2ludDY0RGVmYXVsdGVkEjMKEnNmaXhlZDMyX2RlZmF1bHRlZBiUAyABKA86Ay0zMlIRc2ZpeGVkMzJEZWZhdWx0ZWQSMwoSc2ZpeGVkNjRfZGVmYXVsdGVkGJUDIAEoEDoDLTY0UhFzZml4ZWQ2NERlZmF1bHRlZBJbChFtYXBfc3RyaW5nX3N0cmluZxiWAyADKAsyLi5wcm90by5hcGkudjEuVGVzdFJlcXVlc3QuTWFwU3RyaW5nU3RyaW5nRW50cnlSD21hcFN0cmluZ1N0cmluZxJECg9rbm93bl90aW1lc3RhbXAYlwMgASgLMhouZ29vZ2xlLnByb3RvYnVmLlRpbWVzdGFtcFIOa25vd25UaW1lc3RhbXASQQoOa25vd25fZHVyYXRpb24YmAMgASgLMhkuZ29vZ2xlLnByb3RvYnVmLkR1cmF0aW9uUg1rbm93bkR1cmF0aW9uEjsKDGtub3duX3N0cnVjdBiZAyABKAsyFy5nb29nbGUucHJvdG9idWYuU3RydWN0Ugtrbm93blN0cnVjdBI4Cgtrbm93bl9lbXB0eRiaAyABKAsyFi5nb29nbGUucHJvdG9idWYuRW1wdHlSCmtub3duRW1wdHkSMgoJa25vd25fYW55GJsDIAEoCzIULmdvb2dsZS5wcm90b2J1Zi5BbnlSCGtub3duQW55EjkKBm5lc3RlZBicAyABKAsyIC5wcm90by5hcGkudjEuVGVzdFJlcXVlc3QuTmVzdGVkUgZuZXN0ZWQSFgoGbnVtYmVyGAUgASgFUgZudW1iZXISEgoEbmFtZRgGIAEoCVIEbmFtZRISCgRkYXRhGAcgASgMUgRkYXRhEhUKBnRlbXBfYxgIIAEoAVIFdGVtcEMSMgoDY29sGAkgASgLMiAucHJvdG8uYXBpLnYxLlRlc3RSZXF1ZXN0Lk5lc3RlZFIDY29sEksKCWRlZXBfZW51bRgEIAEoDjIuLnByb3RvLmFwaS52MS5UZXN0UmVxdWVzdC5OZXN0ZWQuRGVlcC5EZWVwRW51bVIIZGVlcEVudW0SRwoLbmVzdGVkX2RlZXAYnQMgASgLMiUucHJvdG8uYXBpLnYxLlRlc3RSZXF1ZXN0Lk5lc3RlZC5EZWVwUgpuZXN0ZWREZWVwEiEKCmxhc3RfZmllbGQY/////wEgASgJUglsYXN0RmllbGQaQgoUTWFwU3RyaW5nU3RyaW5nRW50cnkSEAoDa2V5GAEgASgJUgNrZXkSFAoFdmFsdWUYAiABKAlSBXZhbHVlOgI4ARrEAgoGTmVzdGVkEhIKBG5hbWUYASABKAlSBG5hbWUSFAoFZ3JvdXAYAiABKAlSBWdyb3VwEjkKBGRlZXAYAyABKAsyJS5wcm90by5hcGkudjEuVGVzdFJlcXVlc3QuTmVzdGVkLkRlZXBSBGRlZXAa1AEKBERlZXASEgoEbmFtZRgBIAIoCVIEbmFtZRIUCgVwaG9uZRgCIAEoCVIFcGhvbmUSFAoFZW1haWwYAyABKAlSBWVtYWlsEksKCWRlZXBfZW51bRgEIAEoDjIuLnByb3RvLmFwaS52MS5UZXN0UmVxdWVzdC5OZXN0ZWQuRGVlcC5EZWVwRW51bVIIZGVlcEVudW0iNgoIRGVlcEVudW0SFwoVREVFUF9FTlVNX1VOU1BFQ0lGSUVEEhEKDURFRVBfRU5VTV9GT08QAUIHCgV1bmlvbiI6CgRLaW5kEhIKEEtJTkRfVU5TUEVDSUZJRUQSCAoEVk9JRBABEgoKBlNUUklORxACEggKBEJPT0wQA0IJCgd4X2ZpZWxkIoYCCg5Bbm90aGVyUmVxdWVzdBI4CgZuZXN0ZWQYASABKAsyIC5wcm90by5hcGkudjEuVGVzdFJlcXVlc3QuTmVzdGVkUgZuZXN0ZWQSOQoEZGVlcBgCIAEoCzIlLnByb3RvLmFwaS52MS5UZXN0UmVxdWVzdC5OZXN0ZWQuRGVlcFIEZGVlcBIyCgRraW5kGAMgASgOMh4ucHJvdG8uYXBpLnYxLlRlc3RSZXF1ZXN0LktpbmRSBGtpbmQSSwoJZGVlcF9lbnVtGAQgASgOMi4ucHJvdG8uYXBpLnYxLlRlc3RSZXF1ZXN0Lk5lc3RlZC5EZWVwLkRlZXBFbnVtUghkZWVwRW51bSoyCgNGb28SEQoPRk9PX1VOU1BFQ0lGSUVEEgsKB0ZPT19CQVIQARILCgdGT09fQkFaEAJK00QKBxIFAACVAQEKCAoBDBIDAAASCggKAQISAwIAFQoJCgIDABIDBAApCgkKAgMBEgMFACgKCQoCAwISAwYAJgoJCgIDAxIDBwAlCgkKAgMEEgMIACMKGwoCBQASBAsAEAEaDyBFbnVtIGNvbW1lbnQuCgoKCgMFAAESAwsFCAocCgQFAAIAEgMNBBgaDyBDYXNlIGNvbW1lbnQuCgoMCgUFAAIAARIDDQQTCgwKBQUAAgACEgMNFhcKCwoEBQACARIDDgQQCgwKBQUAAgEBEgMOBAsKDAoF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';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'proto.api.v1.TestRequest' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\XFieldDeepEnum::class),
            'proto.api.v1.TestRequest.Nested' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\Nested::class),
            'proto.api.v1.TestRequest.Nested.Deep' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\Nested\Deep\UnionEmail::class),
            'proto.api.v1.AnotherRequest' => new Pool\MessageMetadata(\Proto\Api\V1\AnotherRequest::class),
            'proto.api.v1.Foo' => new Pool\EnumMetadata(\Proto\Api\V1\Foo::class),
            'proto.api.v1.TestRequest.Kind' => new Pool\EnumMetadata(\Proto\Api\V1\TestRequest\Kind::class),
            'proto.api.v1.TestRequest.Nested.Deep.DeepEnum' => new Pool\EnumMetadata(\Proto\Api\V1\TestRequest\Nested\Deep\DeepEnum::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Proto\Api\V1\Proto2TestDescriptorRegistry()),
);

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'php_namespace/php_namespace.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Api/V1/TestRequest.php',
                        content: self::phpContent(
                            source: 'php_namespace/php_namespace.proto',
                            content: <<<'PHP'
namespace Thesis\Api\V1;

/**
 * @api
 */
final readonly class TestRequest {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Api/V1/PhpNamespacePhpNamespaceDescriptorRegistry.php',
                        content: self::phpContent(
                            source: 'php_namespace/php_namespace.proto',
                            content: <<<'PHP'
namespace Thesis\Api\V1;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class PhpNamespacePhpNamespaceDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'CiFwaHBfbmFtZXNwYWNlL3BocF9uYW1lc3BhY2UucHJvdG8SC3Rlc3QuYXBpLnYxIg0KC1Rlc3RSZXF1ZXN0Qhn4AQHCAgNLZWvKAg1UaGVzaXNcQXBpXFYxSl0KBhIEAAAHFgoICgEMEgMAABIKCAoBCBIDAgApCgkKAggpEgMCACkKCAoBCBIDAwAgCgkKAggoEgMDACAKCAoBAhIDBQAUCgkKAgQAEgMHABYKCgoDBAABEgMHCBNiBnByb3RvMw==';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'test.api.v1.TestRequest' => new Pool\MessageMetadata(\Thesis\Api\V1\TestRequest::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Api/V1/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Thesis\Api\V1\PhpNamespacePhpNamespaceDescriptorRegistry()),
);

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'snake_case/snake_case.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class TestRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\NestedMessage::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage $nested = null,
        #[Reflection\Field(5, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\NestedMessage\Deep::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep $nestedDeep = null,
        #[Reflection\OneOf([
            \Proto\Api\V1\TestRequest\XFieldNumber::class,
            \Proto\Api\V1\TestRequest\XFieldCol::class,
            \Proto\Api\V1\TestRequest\XFieldDeepEnum::class,
        ])]
        public ?\Proto\Api\V1\TestRequest\XField $xField = null,
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
final readonly class XFieldNumber implements \Proto\Api\V1\TestRequest\XField
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
final readonly class XFieldCol implements \Proto\Api\V1\TestRequest\XField
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\NestedMessage::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage $col = null,
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class XFieldDeepEnum implements \Proto\Api\V1\TestRequest\XField
{
    public function __construct(
        #[Reflection\Field(4, new Reflection\EnumT(\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum $deepEnum = null,
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
interface XField {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/TestRequest/NestedMessage.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1\TestRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class NestedMessage
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\NestedMessage\Deep::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep $deep = null,
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Deep
{
    public function __construct(
        #[Reflection\Field(3, new Reflection\EnumT(\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum $deepEnum = null,
        #[Reflection\OneOf([
            \Proto\Api\V1\TestRequest\NestedMessage\Deep\UnionPhone::class,
            \Proto\Api\V1\TestRequest\NestedMessage\Deep\UnionEmail::class,
        ])]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep\Union $union = null,
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
final readonly class UnionPhone implements \Proto\Api\V1\TestRequest\NestedMessage\Deep\Union
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
final readonly class UnionEmail implements \Proto\Api\V1\TestRequest\NestedMessage\Deep\Union
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
interface Union {}

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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class AnotherRequest
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\Proto\Api\V1\TestRequest\NestedMessage::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage $nested = null,
        #[Reflection\Field(2, new Reflection\EnumT(\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum::class))]
        public ?\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum $deepEnum = null,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/SnakeCaseSnakeCaseDescriptorRegistry.php',
                        content: self::phpContent(
                            'snake_case/snake_case.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class SnakeCaseSnakeCaseDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'ChtzbmFrZV9jYXNlL3NuYWtlX2Nhc2UucHJvdG8SDHByb3RvLmFwaS52MSLrBAoMVGVzdF9SZXF1ZXN0EkEKBm5lc3RlZBgBIAEoCzIpLnByb3RvLmFwaS52MS5UZXN0X1JlcXVlc3QuTmVzdGVkX01lc3NhZ2VSBm5lc3RlZBIWCgZudW1iZXIYAiABKAVSBm51bWJlchI7CgNjb2wYAyABKAsyKS5wcm90by5hcGkudjEuVGVzdF9SZXF1ZXN0Lk5lc3RlZF9NZXNzYWdlUgNjb2wSVQoJZGVlcF9lbnVtGAQgASgOMjgucHJvdG8uYXBpLnYxLlRlc3RfUmVxdWVzdC5OZXN0ZWRfTWVzc2FnZS5EZWVwLkRlZXBfRW51bVIIZGVlcEVudW0STwoLbmVzdGVkX2RlZXAYBSABKAsyLi5wcm90by5hcGkudjEuVGVzdF9SZXF1ZXN0Lk5lc3RlZF9NZXNzYWdlLkRlZXBSCm5lc3RlZERlZXAajwIKDk5lc3RlZF9NZXNzYWdlEkIKBGRlZXAYASABKAsyLi5wcm90by5hcGkudjEuVGVzdF9SZXF1ZXN0Lk5lc3RlZF9NZXNzYWdlLkRlZXBSBGRlZXAauAEKBERlZXASFAoFcGhvbmUYASABKAlSBXBob25lEhQKBWVtYWlsGAIgASgJUgVlbWFpbBJVCglkZWVwX2VudW0YAyABKA4yOC5wcm90by5hcGkudjEuVGVzdF9SZXF1ZXN0Lk5lc3RlZF9NZXNzYWdlLkRlZXAuRGVlcF9FbnVtUghkZWVwRW51bSIkCglEZWVwX0VudW0SFwoVREVFUF9FTlVNX1VOU1BFQ0lGSUVEQgcKBXVuaW9uQgkKB3hfZmllbGQiqwEKD0Fub3RoZXJfUmVxdWVzdBJBCgZuZXN0ZWQYASABKAsyKS5wcm90by5hcGkudjEuVGVzdF9SZXF1ZXN0Lk5lc3RlZF9NZXNzYWdlUgZuZXN0ZWQSVQoJZGVlcF9lbnVtGAIgASgOMjgucHJvdG8uYXBpLnYxLlRlc3RfUmVxdWVzdC5OZXN0ZWRfTWVzc2FnZS5EZWVwLkRlZXBfRW51bVIIZGVlcEVudW0qGAoDRm9vEhEKD0ZPT19VTlNQRUNJRklFREr9BwoGEgQAACMBCggKAQwSAwAAEgoICgECEgMCABUKCgoCBQASBAQABgEKCgoDBQABEgMEBQgKCwoEBQACABIDBQQYCgwKBQUAAgABEgMFBBMKDAoFBQACAAISAwUWFwoKCgIEABIECAAeAQoKCgMEAAESAwgIFAoMCgQEAAMAEgQJBBYFCgwKBQQAAwABEgMJDBoKDgoGBAADAAMAEgQKCBQJCg4KBwQAAwADAAESAwoQFAoQCggEAAMAAwAEABIECwwNDQoQCgkEAAMAAwAEAAESAwsRGgoRCgoEAAMAAwAEAAIAEgMMECoKEgoLBAADAAMABAACAAESAwwQJQoSCgsEAAMAAwAEAAIAAhIDDCgpChAKCAQAAwADAAgAEgQPDBINChAKCQQAAwADAAgAARIDDxIXCg8KCAQAAwADAAIAEgMQECEKEAoJBAADAAMAAgAFEgMQEBYKEAoJBAADAAMAAgABEgMQFxwKEAoJBAADAAMAAgADEgMQHyAKDwoIBAADAAMAAgESAxEQIQoQCgkEAAMAAwACAQUSAxEQFgoQCgkEAAMAAwACAQESAxEXHAoQCgkEAAMAAwACAQMSAxEfIAoPCggEAAMAAwACAhIDEwwkChAKCQQAAwADAAICBhIDEwwVChAKCQQAAwADAAICARIDExYfChAKCQQAAwADAAICAxIDEyIjCg0KBgQAAwACABIDFQgWCg4KBwQAAwACAAYSAxUIDAoOCgcEAAMAAgABEgMVDREKDgoHBAADAAIAAxIDFRQVCgsKBAQAAgASAxcEHgoMCgUEAAIABhIDFwQSCgwKBQQAAgABEgMXExkKDAoFBAACAAMSAxccHQoMCgQEAAgAEgQYBBwFCgwKBQQACAABEgMYChEKCwoEBAACARIDGQgZCgwKBQQAAgEFEgMZCA0KDAoFBAACAQESAxkOFAoMCgUEAAIBAxIDGRcYCgsKBAQAAgISAxoIHwoMCgUEAAICBhIDGggWCgwKBQQAAgIBEgMaFxoKDAoFBAACAgMSAxodHgoLCgQEAAIDEgMbCDQKDAoFBAACAwYSAxsIJQoMCgUEAAIDARIDGyYvCgwKBQQAAgMDEgMbMjMKCwoEBAACBBIDHQQoCgwKBQQAAgQGEgMdBBcKDAoFBAACBAESAx0YIwoMCgUEAAIEAxIDHSYnCgoKAgQBEgQgACMBCgoKAwQBARIDIAgXCgsKBAQBAgASAyEEKwoMCgUEAQIABhIDIQQfCgwKBQQBAgABEgMhICYKDAoFBAECAAMSAyEpKgoLCgQEAQIBEgMiBD0KDAoFBAECAQYSAyIELgoMCgUEAQIBARIDIi84CgwKBQQBAgEDEgMiOzxiBnByb3RvMw==';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'proto.api.v1.Test_Request' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\XFieldDeepEnum::class),
            'proto.api.v1.Test_Request.Nested_Message' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\NestedMessage::class),
            'proto.api.v1.Test_Request.Nested_Message.Deep' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\NestedMessage\Deep\UnionEmail::class),
            'proto.api.v1.Another_Request' => new Pool\MessageMetadata(\Proto\Api\V1\AnotherRequest::class),
            'proto.api.v1.Foo' => new Pool\EnumMetadata(\Proto\Api\V1\Foo::class),
            'proto.api.v1.Test_Request.Nested_Message.Deep.Deep_Enum' => new Pool\EnumMetadata(\Proto\Api\V1\TestRequest\NestedMessage\Deep\DeepEnum::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Proto\Api\V1\SnakeCaseSnakeCaseDescriptorRegistry()),
);

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'reserved_names/reserved_names.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
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
final readonly class Exit_ {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/INSTANCEOF_.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class INSTANCEOF_
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\ReservedTypes\Class_\Case_\Break_::class))]
        public ?\ReservedTypes\Class_\Case_\Break_ $break = null,
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

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Class_
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(\ReservedTypes\Class_\Case_\Break_::class))]
        public ?\ReservedTypes\Class_\Case_\Break_ $break = null,
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
final readonly class Case_ {}

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
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/ReservedNamesReservedNamesDescriptorRegistry.php',
                        content: self::phpContent(
                            'reserved_names/reserved_names.proto',
                            <<<'PHP'
namespace ReservedTypes;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class ReservedNamesReservedNamesDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'CiNyZXNlcnZlZF9uYW1lcy9yZXNlcnZlZF9uYW1lcy5wcm90bxIOcmVzZXJ2ZWRfdHlwZXMiBgoEZXhpdCJECgpJTlNUQU5DRU9GEjYKBWJyZWFrGAEgASgLMiAucmVzZXJ2ZWRfdHlwZXMuQ2xhc3MuQ2FzZS5CcmVha1IFYnJlYWsiZAoFQ2xhc3MSNgoFYnJlYWsYASABKAsyIC5yZXNlcnZlZF90eXBlcy5DbGFzcy5DYXNlLkJyZWFrUgVicmVhaxojCgRDYXNlGhsKBUJyZWFrEhIKBG5hbWUYASABKAlSBG5hbWUqQgoKTm90QWxsb3dlZBIKCghhYnN0cmFjdBIHCgNhbmQQARIJCgVhcnJheRACEgkKBWVtcHR5EAMSCQoFY2xhc3MQBCoRCgZzdHJpbmcSBwoFWkVSTzEqEAoFVHJhaXQSBwoFWkVSTzJKuQUKBhIEAAAeAQoICgEMEgMAABIKCAoBAhIDAgAXCgoKAgUAEgQEAAoBCgoKAwUAARIDBAUPCgsKBAUAAgASAwUEEQoMCgUFAAIAARIDBQQMCgwKBQUAAgACEgMFDxAKCwoEBQACARIDBgQMCgwKBQUAAgEBEgMGBAcKDAoFBQACAQISAwYKCwoLCgQFAAICEgMHBA4KDAoFBQACAgESAwcECQoMCgUFAAICAhIDBwwNCgsKBAUAAgMSAwgEDgoMCgUFAAIDARIDCAQJCgwKBQUAAgMCEgMIDA0KCwoEBQACBBIDCQQOCgwKBQUAAgQBEgMJBAkKDAoFBQACBAISAwkMDQoJCgIFARIDDAAaCgoKAwUBARIDDAULCgsKBAUBAgASAwwOGAoMCgUFAQIAARIDDA4TCgwKBQUBAgACEgMMFhcKCQoCBQISAw4AGQoKCgMFAgESAw4FCgoLCgQFAgIAEgMODRcKDAoFBQICAAESAw4NEgoMCgUFAgIAAhIDDhUWCgkKAgQAEgMQAA8KCgoDBAABEgMQCAwKCgoCBAESBBIAFAEKCgoDBAEBEgMSCBIKCwoEBAECABIDEwQfCgwKBQQBAgAGEgMTBBQKDAoFBAECAAESAxMVGgoMCgUEAQIAAxIDEx0eCgoKAgQCEgQWAB4BCgoKAwQCARIDFggNCgwKBAQCAwASBBcEGwUKDAoFBAIDAAESAxcMEAoOCgYEAgMAAwASBBgIGgkKDgoHBAIDAAMAARIDGBAVCg8KCAQCAwADAAIAEgMZDBwKEAoJBAIDAAMAAgAFEgMZDBIKEAoJBAIDAAMAAgABEgMZExcKEAoJBAIDAAMAAgADEgMZGhsKCwoEBAICABIDHQQZCgwKBQQCAgAGEgMdBA4KDAoFBAICAAESAx0PFAoMCgUEAgIAAxIDHRcYYgZwcm90bzM=';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'reserved_types.exit' => new Pool\MessageMetadata(\ReservedTypes\Exit_::class),
            'reserved_types.INSTANCEOF' => new Pool\MessageMetadata(\ReservedTypes\INSTANCEOF_::class),
            'reserved_types.Class' => new Pool\MessageMetadata(\ReservedTypes\Class_::class),
            'reserved_types.Class.Case' => new Pool\MessageMetadata(\ReservedTypes\Class_\Case_::class),
            'reserved_types.Class.Case.Break' => new Pool\MessageMetadata(\ReservedTypes\Class_\Case_\Break_::class),
            'reserved_types.NotAllowed' => new Pool\EnumMetadata(\ReservedTypes\NotAllowed::class),
            'reserved_types.string' => new Pool\EnumMetadata(\ReservedTypes\String_::class),
            'reserved_types.Trait' => new Pool\EnumMetadata(\ReservedTypes\Trait_::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'ReservedTypes/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \ReservedTypes\ReservedNamesReservedNamesDescriptorRegistry()),
);

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'proto3/test.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
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
        #[Reflection\OneOf([\Proto\Api\V1\TestRequest\ContactPhone::class, \Proto\Api\V1\TestRequest\ContactEmail::class])]
        public ?\Proto\Api\V1\TestRequest\Contact $contact = null,
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
final readonly class ContactPhone implements \Proto\Api\V1\TestRequest\Contact
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
final readonly class ContactEmail implements \Proto\Api\V1\TestRequest\Contact
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
interface Contact {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/Proto3TestDescriptorRegistry.php',
                        content: self::phpContent(
                            'proto3/test.proto',
                            <<<'PHP'
namespace Proto\Api\V1;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class Proto3TestDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'ChFwcm90bzMvdGVzdC5wcm90bxIMcHJvdG8uYXBpLnYxIroBCgtUZXN0UmVxdWVzdBIhCgxzdHJpbmdfdmFsdWUYASABKAlSC3N0cmluZ1ZhbHVlEjcKFW9wdGlvbmFsX3N0cmluZ192YWx1ZRgCIAEoCUgBUhNvcHRpb25hbFN0cmluZ1ZhbHVliAEBEhQKBXBob25lGAMgASgJUgVwaG9uZRIUCgVlbWFpbBgEIAEoCVIFZW1haWxCCQoHY29udGFjdEIYChZfb3B0aW9uYWxfc3RyaW5nX3ZhbHVlSroCCgYSBAAACwEKCAoBDBIDAAASCggKAQISAwIAFQoKCgIEABIEBAALAQoKCgMEAAESAwQIEwoLCgQEAAIAEgMFBBwKDAoFBAACAAUSAwUECgoMCgUEAAIAARIDBQsXCgwKBQQAAgADEgMFGhsKCwoEBAACARIDBgQuCgwKBQQAAgEEEgMGBAwKDAoFBAACAQUSAwYNEwoMCgUEAAIBARIDBhQpCgwKBQQAAgEDEgMGLC0KDAoEBAAIABIEBwQKBQoMCgUEAAgAARIDBwoRCgsKBAQAAgISAwgIGQoMCgUEAAICBRIDCAgOCgwKBQQAAgIBEgMIDxQKDAoFBAACAgMSAwgXGAoLCgQEAAIDEgMJCBkKDAoFBAACAwUSAwkIDgoMCgUEAAIDARIDCQ8UCgwKBQQAAgMDEgMJFxhiBnByb3RvMw==';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'proto.api.v1.TestRequest' => new Pool\MessageMetadata(\Proto\Api\V1\TestRequest\ContactEmail::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Proto/Api/V1/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Proto\Api\V1\Proto3TestDescriptorRegistry()),
);

PHP,
                        ),
                    ),
                ],
            ),
        ];

        yield [
            'grpc/test.txt',
            new CodeGeneratorResponse(
                supportedFeatures: new Number(Compiler::SUPPORTED_FEATURES),
                file: [
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/V1/AuthServiceClient.php',
                        content: self::phpContent(
                            'auth_v1.proto',
                            <<<'PHP'
namespace Thesis\Auth\V1;

use Amp\Cancellation;
use Amp\NullCancellation;
use Thesis\Grpc\Client;
use Thesis\Grpc\Metadata;

/**
 * @api
 */
final readonly class AuthServiceClient
{
    public function __construct(
        private Client $client,
    ) {}

    public function login(
        \Thesis\Auth\LoginRequest $request,
        Metadata $md = new Metadata(),
        Cancellation $cancellation = new NullCancellation(),
    ): \Thesis\Auth\LoginResponse {
        /** @var Client\Invoke<\Thesis\Auth\LoginRequest, \Thesis\Auth\LoginResponse> $invoke */
        $invoke = new Client\Invoke(
            method: '/Thesis.Auth.V1.AuthService/Login',
            type: \Thesis\Auth\LoginResponse::class,
        );

        return $this->client->invoke(
            request: $request,
            invoke: $invoke,
            md: $md,
            cancellation: $cancellation,
        );
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/V1/AuthServiceServer.php',
                        content: self::phpContent(
                            'auth_v1.proto',
                            <<<'PHP'
namespace Thesis\Auth\V1;

use Amp\Cancellation;
use Thesis\Grpc\Metadata;

/**
 * @api
 */
interface AuthServiceServer
{
    public function login(
        \Thesis\Auth\LoginRequest $request,
        Metadata $md,
        Cancellation $cancellation,
    ): \Thesis\Auth\LoginResponse;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/V1/AuthServiceServerRegistry.php',
                        content: self::phpContent(
                            'auth_v1.proto',
                            <<<'PHP'
namespace Thesis\Auth\V1;

use Override;
use Thesis\Grpc\Server;

/**
 * @api
 */
final readonly class AuthServiceServerRegistry implements Server\ServiceRegistry
{
    public function __construct(
        private \Thesis\Auth\V1\AuthServiceServer $server,
    ) {}

    #[Override]
    public function services(): iterable
    {
        yield new Server\Service('Thesis.Auth.V1.AuthService', [
            new Server\Rpc(
                new Server\Handle('Login', \Thesis\Auth\LoginRequest::class),
                new Server\UnaryHandler($this->server->login(...)),
            ),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/V1/QueueServiceClient.php',
                        content: self::phpContent(
                            'queue_v1.proto',
                            <<<'PHP'
namespace Thesis\Queue\V1;

use Amp\Cancellation;
use Amp\NullCancellation;
use Thesis\Grpc\Client;
use Thesis\Grpc\Metadata;

/**
 * @api
 */
final readonly class QueueServiceClient
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * @return Client\ClientStreamChannel<\Thesis\Queue\PushRequest\Message, \Google\Protobuf\Empty_>
     */
    public function push(
        Metadata $md = new Metadata(),
        Cancellation $cancellation = new NullCancellation(),
    ): Client\ClientStreamChannel {
        /** @var Client\Invoke<\Thesis\Queue\PushRequest\Message, \Google\Protobuf\Empty_> $invoke */
        $invoke = new Client\Invoke(
            method: '/Thesis.Queue.V1.QueueService/Push',
            type: \Google\Protobuf\Empty_::class,
        );

        $stream = $this->client->createStream(
            invoke: $invoke,
            md: $md,
            cancellation: $cancellation,
        );

        return new Client\ClientStreamChannel($stream);
    }

    /**
     * @return Client\ServerStreamChannel<\Thesis\Queue\PullRequest, \Thesis\Queue\PullRequest\Message>
     */
    public function pull(
        \Thesis\Queue\PullRequest $request,
        Metadata $md = new Metadata(),
        Cancellation $cancellation = new NullCancellation(),
    ): Client\ServerStreamChannel {
        /** @var Client\Invoke<\Thesis\Queue\PullRequest, \Thesis\Queue\PullRequest\Message> $invoke */
        $invoke = new Client\Invoke(
            method: '/Thesis.Queue.V1.QueueService/Pull',
            type: \Thesis\Queue\PullRequest\Message::class,
        );

        $stream = $this->client->createStream(
            invoke: $invoke,
            md: $md,
            cancellation: $cancellation,
        );

        $stream->send($request);
        $stream->close();

        return new Client\ServerStreamChannel($stream);
    }

    /**
     * @return Client\BidirectionalStreamChannel<\Thesis\Queue\Heartbeat\FromClient\Ping, \Thesis\Queue\Heartbeat\FromServer\Ping>
     */
    public function heartbeat(
        Metadata $md = new Metadata(),
        Cancellation $cancellation = new NullCancellation(),
    ): Client\BidirectionalStreamChannel {
        /** @var Client\Invoke<\Thesis\Queue\Heartbeat\FromClient\Ping, \Thesis\Queue\Heartbeat\FromServer\Ping> $invoke */
        $invoke = new Client\Invoke(
            method: '/Thesis.Queue.V1.QueueService/Heartbeat',
            type: \Thesis\Queue\Heartbeat\FromServer\Ping::class,
        );

        $stream = $this->client->createStream(
            invoke: $invoke,
            md: $md,
            cancellation: $cancellation,
        );

        return new Client\BidirectionalStreamChannel($stream);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/V1/QueueServiceServer.php',
                        content: self::phpContent(
                            'queue_v1.proto',
                            <<<'PHP'
namespace Thesis\Queue\V1;

use Amp\Cancellation;
use Thesis\Grpc\Metadata;
use Thesis\Grpc\Server;

/**
 * @api
 */
interface QueueServiceServer
{
    /**
     * @param Server\ClientStreamChannel<\Thesis\Queue\PushRequest\Message, \Google\Protobuf\Empty_> $stream
     */
    public function push(Server\ClientStreamChannel $stream, Metadata $md, Cancellation $cancellation): void;

    /**
     * @param Server\ServerStreamChannel<\Thesis\Queue\PullRequest, \Thesis\Queue\PullRequest\Message> $stream
     */
    public function pull(
        \Thesis\Queue\PullRequest $request,
        Server\ServerStreamChannel $stream,
        Metadata $md,
        Cancellation $cancellation,
    ): void;

    /**
     * @param Server\BidirectionalStreamChannel<\Thesis\Queue\Heartbeat\FromClient\Ping, \Thesis\Queue\Heartbeat\FromServer\Ping> $stream
     */
    public function heartbeat(
        Server\BidirectionalStreamChannel $stream,
        Metadata $md,
        Cancellation $cancellation,
    ): void;
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/V1/QueueServiceServerRegistry.php',
                        content: self::phpContent(
                            'queue_v1.proto',
                            <<<'PHP'
namespace Thesis\Queue\V1;

use Override;
use Thesis\Grpc\Server;

/**
 * @api
 */
final readonly class QueueServiceServerRegistry implements Server\ServiceRegistry
{
    public function __construct(
        private \Thesis\Queue\V1\QueueServiceServer $server,
    ) {}

    #[Override]
    public function services(): iterable
    {
        yield new Server\Service('Thesis.Queue.V1.QueueService', [
            new Server\Rpc(
                new Server\Handle('Push', \Thesis\Queue\PushRequest\Message::class),
                new Server\ClientStreamHandler($this->server->push(...)),
            ),
            new Server\Rpc(
                new Server\Handle('Pull', \Thesis\Queue\PullRequest::class),
                new Server\ServerStreamHandler($this->server->pull(...)),
            ),
            new Server\Rpc(
                new Server\Handle('Heartbeat', \Thesis\Queue\Heartbeat\FromClient\Ping::class),
                new Server\BidirectionalStreamHandler($this->server->heartbeat(...)),
            ),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/LoginRequest.php',
                        content: self::phpContent(
                            'protos/auth.proto',
                            <<<'PHP'
namespace Thesis\Auth;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class LoginRequest
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $user = '',
        #[Reflection\Field(3, Reflection\StringT::T)]
        public string $password = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/LoginResponse.php',
                        content: self::phpContent(
                            'protos/auth.proto',
                            <<<'PHP'
namespace Thesis\Auth;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class LoginResponse
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $token = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Auth/ProtosAuthDescriptorRegistry.php',
                        content: self::phpContent(
                            'protos/auth.proto',
                            <<<'PHP'
namespace Thesis\Auth;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class ProtosAuthDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'ChFwcm90b3MvYXV0aC5wcm90bxILVGhlc2lzLkF1dGgiPgoMTG9naW5SZXF1ZXN0EhIKBHVzZXIYAiABKAlSBHVzZXISGgoIcGFzc3dvcmQYAyABKAlSCHBhc3N3b3JkIiUKDUxvZ2luUmVzcG9uc2USFAoFdG9rZW4YASABKAlSBXRva2VuSvEBCgYSBAAACwEKCAoBDBIDAAASCggKAQISAwIAFAoKCgIEABIEBAAHAQoKCgMEAAESAwQIFAoLCgQEAAIAEgMFBBQKDAoFBAACAAUSAwUECgoMCgUEAAIAARIDBQsPCgwKBQQAAgADEgMFEhMKCwoEBAACARIDBgQYCgwKBQQAAgEFEgMGBAoKDAoFBAACAQESAwYLEwoMCgUEAAIBAxIDBhYXCgoKAgQBEgQJAAsBCgoKAwQBARIDCQgVCgsKBAQBAgASAwoEFQoMCgUEAQIABRIDCgQKCgwKBQQBAgABEgMKCxAKDAoFBAECAAMSAwoTFGIGcHJvdG8z';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'Thesis.Auth.LoginRequest' => new Pool\MessageMetadata(\Thesis\Auth\LoginRequest::class),
            'Thesis.Auth.LoginResponse' => new Pool\MessageMetadata(\Thesis\Auth\LoginResponse::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/PushRequest.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue;

/**
 * @api
 */
final readonly class PushRequest {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/PushRequest/Message.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\PushRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Message
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $content = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/PullRequest.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class PullRequest
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $qos = 0,
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/PullRequest/Message.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\PullRequest;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Message
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $content = '',
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $id = '',
    ) {}
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/Heartbeat.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue;

/**
 * @api
 */
final readonly class Heartbeat {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/Heartbeat/FromClient.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\Heartbeat;

/**
 * @api
 */
final readonly class FromClient {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/Heartbeat/FromClient/Ping.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\Heartbeat\FromClient;

/**
 * @api
 */
final readonly class Ping {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/Heartbeat/FromServer.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\Heartbeat;

/**
 * @api
 */
final readonly class FromServer {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/Heartbeat/FromServer/Ping.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue\Heartbeat\FromServer;

/**
 * @api
 */
final readonly class Ping {}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/Queue/ProtosQueueDescriptorRegistry.php',
                        content: self::phpContent(
                            'protos/queue.proto',
                            <<<'PHP'
namespace Thesis\Queue;

use Override;
use Thesis\Protobuf\Pool;

/**
 * @api
 */
final readonly class ProtosQueueDescriptorRegistry implements Pool\Registrar
{
    private const string DESCRIPTOR_BUFFER = 'ChJwcm90b3MvcXVldWUucHJvdG8SDFRoZXNpcy5RdWV1ZSIyCgtQdXNoUmVxdWVzdBojCgdNZXNzYWdlEhgKB2NvbnRlbnQYASABKAlSB2NvbnRlbnQiVAoLUHVsbFJlcXVlc3QSEAoDcW9zGAEgASgFUgNxb3MaMwoHTWVzc2FnZRIYCgdjb250ZW50GAEgASgJUgdjb250ZW50Eg4KAmlkGAIgASgJUgJpZCI3CglIZWFydGJlYXQaFAoKRnJvbUNsaWVudBoGCgRQaW5nGhQKCkZyb21TZXJ2ZXIaBgoEUGluZ0qGBAoGEgQAABsBCggKAQwSAwAAEgoICgECEgMCABUKCgoCBAASBAQACAEKCgoDBAABEgMECBMKDAoEBAADABIEBQQHBQoMCgUEAAMAARIDBQwTCg0KBgQAAwACABIDBggbCg4KBwQAAwACAAUSAwYIDgoOCgcEAAMAAgABEgMGDxYKDgoHBAADAAIAAxIDBhkaCgoKAgQBEgQKABEBCgoKAwQBARIDCggTCgwKBAQBAwASBAsEDgUKDAoFBAEDAAESAwsMEwoNCgYEAQMAAgASAwwIGwoOCgcEAQMAAgAFEgMMCA4KDgoHBAEDAAIAARIDDA8WCg4KBwQBAwACAAMSAwwZGgoNCgYEAQMAAgESAw0IFgoOCgcEAQMAAgEFEgMNCA4KDgoHBAEDAAIBARIDDQ8RCg4KBwQBAwACAQMSAw0UFQoLCgQEAQIAEgMQBBIKDAoFBAECAAUSAxAECQoMCgUEAQIAARIDEAoNCgwKBQQBAgADEgMQEBEKCgoCBAISBBMAGwEKCgoDBAIBEgMTCBEKDAoEBAIDABIEFAQWBQoMCgUEAgMAARIDFAwWCg0KBgQCAwADABIDFQgXCg4KBwQCAwADAAESAxUQFAoMCgQEAgMBEgQYBBoFCgwKBQQCAwEBEgMYDBYKDQoGBAIDAQMAEgMZCBcKDgoHBAIDAQMAARIDGRAUYgZwcm90bzM=';

    #[Override]
    public function register(Pool\Registry $pool): void
    {
        $pool->add(Pool\Descriptor::base64(self::DESCRIPTOR_BUFFER), [
            'Thesis.Queue.PushRequest' => new Pool\MessageMetadata(\Thesis\Queue\PushRequest::class),
            'Thesis.Queue.PushRequest.Message' => new Pool\MessageMetadata(\Thesis\Queue\PushRequest\Message::class),
            'Thesis.Queue.PullRequest' => new Pool\MessageMetadata(\Thesis\Queue\PullRequest::class),
            'Thesis.Queue.PullRequest.Message' => new Pool\MessageMetadata(\Thesis\Queue\PullRequest\Message::class),
            'Thesis.Queue.Heartbeat' => new Pool\MessageMetadata(\Thesis\Queue\Heartbeat::class),
            'Thesis.Queue.Heartbeat.FromClient' => new Pool\MessageMetadata(\Thesis\Queue\Heartbeat\FromClient::class),
            'Thesis.Queue.Heartbeat.FromClient.Ping' => new Pool\MessageMetadata(\Thesis\Queue\Heartbeat\FromClient\Ping::class),
            'Thesis.Queue.Heartbeat.FromServer' => new Pool\MessageMetadata(\Thesis\Queue\Heartbeat\FromServer::class),
            'Thesis.Queue.Heartbeat.FromServer.Ping' => new Pool\MessageMetadata(\Thesis\Queue\Heartbeat\FromServer\Ping::class),
        ]);
    }
}

PHP,
                        ),
                    ),
                    new CodeGeneratorResponse\File(
                        name: 'Thesis/autoload.metadata.php',
                        content: self::autoloadContent(
                            <<<'PHP'
\Thesis\Protobuf\Pool\Registry::get()->register(
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Thesis\Auth\ProtosAuthDescriptorRegistry()),
    new \Thesis\Protobuf\Pool\OnceRegistrar(new \Thesis\Queue\ProtosQueueDescriptorRegistry()),
);

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
            Package\version('thesis/protoc-plugin'),
            $source,
            $content,
        );
    }

    private static function autoloadContent(string $content): string
    {
        return \sprintf(
            <<<'PHP'
<?php

/**
 * Code generated by thesis/protoc-plugin. DO NOT EDIT.
 * Versions:
 *   thesis/protoc-plugin — v%s
 *   protoc               — v6.32.1
 */

declare(strict_types=1);

%s
PHP,
            Package\version('thesis/protoc-plugin'),
            $content,
        );
    }
}
