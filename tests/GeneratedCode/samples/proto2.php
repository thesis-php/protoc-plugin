<?php

declare(strict_types=1);

/**
 * Populated messages of the proto2 snapshot case, keyed by label (see GeneratedCodeTest).
 */

use BcMath\Number;
use Proto\Api\V1\AnotherRequest;
use Proto\Api\V1\Foo;
use Proto\Api\V1\TestRequest;
use Proto\Api\V1\TestRequest\Kind;
use Proto\Api\V1\TestRequest\Nested;
use Proto\Api\V1\TestRequest\Nested\Deep;
use Thesis\Google\Protobuf\Duration;
use Thesis\Google\Protobuf\Timestamp;
use Thesis\Protobuf\Map;

$deep = new Deep(
    name: 'deep',
    deepEnum: Deep\DeepEnum::DEEP_ENUM_FOO,
    union: new Deep\UnionEmail('user@example.com'),
);

$required = static fn(mixed ...$fields): TestRequest => new TestRequest(...[
    'boolRequired' => true,
    'int32Required' => -42,
    'int64Required' => PHP_INT_MIN,
    'fixed32Required' => 4_294_967_295,
    'fixed64Required' => new Number('18446744073709551615'),
    'uint32Required' => 7,
    'uint64Required' => new Number('9223372036854775808'),
    'gloatRequired' => 1.5,
    'doubleRequired' => -0.25,
    'stringRequired' => 'строка',
    'bytesRequired' => "\x00\x01\xff",
    'sint32Required' => -1,
    'sint64Required' => PHP_INT_MAX,
    'sfixed32Required' => -2_147_483_648,
    'sfixed64Required' => -3,
    ...$fields,
]);

return [
    'scalars, repeated, map, well-known types, nested messages and oneof' => $required(
        kind: Kind::BOOL,
        boolOptional: false,
        int32Optional: 0,
        stringOptional: '',
        int32Repeated: [1, -1, 0],
        stringRepeated: ['a', '', 'c'],
        uint64Repeated: [new Number('1'), new Number('18446744073709551615')],
        doubleRepeatedPacked: [0.5, -2.0],
        sint64RepeatedPacked: [-5, 5],
        fooDefaulted: Foo::FOO_BAZ,
        mapStringString: Map::fromArray(['a' => 'b', '' => 'empty key']),
        knownTimestamp: new Timestamp(1_700_000_000, 123),
        knownDuration: new Duration(-5, -500),
        nested: new Nested(name: 'nested', deep: $deep),
        nestedDeep: new Deep('bare'),
        lastField: 'max tag',
        xField: new TestRequest\XFieldCol(new Nested(group: 'group')),
    ),
    'oneof with an enum variant' => $required(
        xField: new TestRequest\XFieldDeepEnum(Deep\DeepEnum::DEEP_ENUM_UNSPECIFIED),
    ),
    'required fields set to zero values' => new TestRequest(
        boolRequired: false,
        int32Required: 0,
        int64Required: 0,
        fixed32Required: 0,
        fixed64Required: new Number(0),
        uint32Required: 0,
        uint64Required: new Number(0),
        gloatRequired: 0.0,
        doubleRequired: 0.0,
        stringRequired: '',
        bytesRequired: '',
        sint32Required: 0,
        sint64Required: 0,
        sfixed32Required: 0,
        sfixed64Required: 0,
    ),
    'required enum with a default set to the zero case' => $required(
        kind: Kind::KIND_UNSPECIFIED,
    ),
    'optional enum with a default set to the zero case' => $required(
        fooDefaulted: Foo::FOO_UNSPECIFIED,
    ),
    'message fields and enums of nested types' => new AnotherRequest(
        nested: new Nested(name: ''),
        deep: $deep,
        kind: Kind::KIND_UNSPECIFIED,
        deepEnum: Deep\DeepEnum::DEEP_ENUM_FOO,
    ),
];
