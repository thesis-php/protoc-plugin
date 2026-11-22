<?php

declare(strict_types=1);

/**
 * Samples of the field_defaults snapshot case, keyed by label (see GeneratedCodeTest): populated messages and checks
 * of the defaults the messages are created with.
 */

use BcMath\Number;
use Field\Defaults\V1\Defaults;
use Field\Defaults\V1\EditionDefaults;
use Field\Defaults\V1\Keyword;
use Thesis\Protobuf\Encoder;

/**
 * @param array<string, array{mixed, mixed}> $values property => [actual, expected], compared with ===
 */
$expect = static function (array $values): void {
    foreach ($values as $property => [$actual, $expected]) {
        if ($actual instanceof Number) {
            $actual = (string) $actual;
        }

        if ($actual !== $expected) {
            throw new LogicException(sprintf('%s: expected %s, got %s', $property, var_export($expected, true), var_export($actual, true)));
        }
    }
};

return [
    'proto2 defaults' => static function () use ($expect): void {
        $message = new Defaults();

        $expect([
            'doubleInf is +INF' => [is_infinite($message->doubleInf) && $message->doubleInf > 0, true],
            'doubleNegInf is -INF' => [is_infinite($message->doubleNegInf) && $message->doubleNegInf < 0, true],
            'doubleNan is NAN' => [is_nan($message->doubleNan), true],
            'floatInf is +INF' => [is_infinite($message->floatInf) && $message->floatInf > 0, true],
            'floatNegInf is -INF' => [is_infinite($message->floatNegInf) && $message->floatNegInf < 0, true],
            'floatNan is NAN' => [is_nan($message->floatNan), true],
            'doubleExponent' => [$message->doubleExponent, 1.5e300],
            'floatExponent' => [$message->floatExponent, -2 ** -13],
            'doubleIntegral' => [$message->doubleIntegral, 42.0],
            'doubleMax' => [$message->doubleMax, PHP_FLOAT_MAX],
            'floatFraction' => [$message->floatFraction, 314_159.0],
            'uint64Max' => [$message->uint64Max, '18446744073709551615'],
            'fixed64Max' => [$message->fixed64Max, '18446744073709551615'],
            'uint64AboveInt' => [$message->uint64AboveInt, '9223372036854775808'],
            'uint64IntMax' => [$message->uint64IntMax, '9223372036854775807'],
            'fixed64Small' => [$message->fixed64Small, '6400'],
            'int64Min' => [$message->int64Min, PHP_INT_MIN],
            'int64Max' => [$message->int64Max, PHP_INT_MAX],
            'sint64Min' => [$message->sint64Min, PHP_INT_MIN],
            'sfixed64Min' => [$message->sfixed64Min, PHP_INT_MIN],
            'sfixed64Max' => [$message->sfixed64Max, PHP_INT_MAX],
            'int32Min' => [$message->int32Min, -(2 ** 31)],
            'int32Max' => [$message->int32Max, 2 ** 31 - 1],
            'sint32Min' => [$message->sint32Min, -(2 ** 31)],
            'sfixed32Min' => [$message->sfixed32Min, -(2 ** 31)],
            'uint32Max' => [$message->uint32Max, 2 ** 32 - 1],
            'fixed32Max' => [$message->fixed32Max, 2 ** 32 - 1],
            'boolTrue' => [$message->boolTrue, true],
            'bytesOctal' => [bin2hex($message->bytesOctal), '0102ff'],
            // q " u o t e ' \ b a c k NUL n u l 0x80 0xFE LF CR TAB $ x
            'bytesSpecial' => [bin2hex($message->bytesSpecial), '7122756f7465275c6261636b006e756c80fe0a0d092478'],
            'stringPhp' => [$message->stringPhp, '$var {$x} \ "q" \'a\' ' . "\n"],
            'stringEscapeLike' => [bin2hex($message->stringEscapeLike), '615c30303162'],
            'stringUnicode' => [$message->stringUnicode, 'привет'],
            'keywordReserved' => [$message->keywordReserved, Keyword::class_],
            'keywordDropped' => [$message->keywordDropped, Keyword::class_],
            'keywordAlias' => [$message->keywordAlias, Keyword::other],
        ]);

        $bytes = Encoder\Builder::buildDefault()->encode($message);

        $expect([
            // tag 20 (varint), 2^64 - 1
            'uint64Max on the wire' => [str_contains($bytes, "\xA0\x01" . str_repeat("\xFF", 9) . "\x01"), true],
            // tag 21 (fixed64), 2^64 - 1
            'fixed64Max on the wire' => [str_contains($bytes, "\xA9\x01" . str_repeat("\xFF", 8)), true],
            // tag 22 (varint), 2^63
            'uint64AboveInt on the wire' => [str_contains($bytes, "\xB0\x01" . str_repeat("\x80", 9) . "\x01"), true],
            // tag 30 (varint), -2^63
            'int64Min on the wire' => [str_contains($bytes, "\xF0\x01" . str_repeat("\x80", 9) . "\x01"), true],
            // tag 61 (length-delimited), 23 bytes
            'bytesSpecial on the wire' => [str_contains($bytes, "\xEA\x03\x17" . $message->bytesSpecial), true],
        ]);
    },
    'editions defaults' => static function () use ($expect): void {
        $message = new EditionDefaults();

        $expect([
            'doubleNan is NAN' => [is_nan($message->doubleNan), true],
            'floatNegInf is -INF' => [is_infinite($message->floatNegInf) && $message->floatNegInf < 0, true],
            'uint64Max' => [$message->uint64Max, '18446744073709551615'],
            'int64Min' => [$message->int64Min, PHP_INT_MIN],
            'bytesEscaped' => [bin2hex($message->bytesEscaped), '005cff'],
            'doubleInfExplicit' => [$message->doubleInfExplicit, null],
            'keywordReserved' => [$message->keywordReserved, Keyword::class_],
        ]);
    },
    'defaults overridden' => new Defaults(
        doubleInf: -0.5,
        doubleNan: 1.0,
        floatNan: -\INF,
        uint64Max: new Number('18446744073709551614'),
        int64Min: PHP_INT_MIN + 1,
        bytesOctal: implode('', array_map(chr(...), range(0, 255))),
        stringPhp: '{$a}',
    ),
];
