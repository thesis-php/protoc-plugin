<?php

declare(strict_types=1);

/**
 * Samples of the proto3_packed snapshot case, keyed by label (see GeneratedCodeTest): a populated message and a check
 * of the wire encoding every repeated field is written in.
 */

use BcMath\Number;
use Proto3\Packed\V1\Color;
use Proto3\Packed\V1\Inner;
use Proto3\Packed\V1\Repeated;
use Thesis\Protobuf\Encoder;

return [
    'all fields set' => new Repeated(
        int32Default: [1, -2, 3],
        int32Packed: [4, 5],
        int32Unpacked: [-6, 7],
        enumDefault: [Color::COLOR_RED, Color::COLOR_GREEN],
        enumPacked: [Color::COLOR_GREEN],
        enumUnpacked: [Color::COLOR_RED, Color::COLOR_UNSPECIFIED],
        boolDefault: [true, false],
        boolPacked: [false, true],
        boolUnpacked: [true, true, false],
        sint64Unpacked: [-1, 1],
        fixed32Unpacked: [1, 4_294_967_295],
        doubleUnpacked: [1.5, -0.25],
        uint64Unpacked: [new Number(1), new Number('18446744073709551615')],
        stringDefault: ['a', ''],
        stringUnpacked: ['b'],
        bytesUnpacked: ["\x00\xff"],
        messageUnpacked: [new Inner(1), new Inner()],
    ),
    'wire encoding follows the packed option' => static function (): void {
        $encoder = Encoder\Builder::buildDefault();

        $expected = [
            // packed by default and with [packed = true]: one length-delimited record
            'int32Default' => [new Repeated(int32Default: [1, 2]), "\x0A\x02\x01\x02"],
            'int32Packed' => [new Repeated(int32Packed: [1, 2]), "\x12\x02\x01\x02"],
            'enumDefault' => [new Repeated(enumDefault: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x22\x02\x01\x02"],
            'enumPacked' => [new Repeated(enumPacked: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x2A\x02\x01\x02"],
            'boolDefault' => [new Repeated(boolDefault: [true, false]), "\x3A\x02\x01\x00"],
            'boolPacked' => [new Repeated(boolPacked: [true, false]), "\x42\x02\x01\x00"],
            // [packed = false]: a record per element
            'int32Unpacked' => [new Repeated(int32Unpacked: [1, 2]), "\x18\x01\x18\x02"],
            'enumUnpacked' => [new Repeated(enumUnpacked: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x30\x01\x30\x02"],
            'boolUnpacked' => [new Repeated(boolUnpacked: [true, false]), "\x48\x01\x48\x00"],
            'sint64Unpacked' => [new Repeated(sint64Unpacked: [-1, 1]), "\x50\x01\x50\x02"],
            'fixed32Unpacked' => [new Repeated(fixed32Unpacked: [1, 2]), "\x5D\x01\x00\x00\x00\x5D\x02\x00\x00\x00"],
            'doubleUnpacked' => [new Repeated(doubleUnpacked: [1.5]), "\x61" . pack('e', 1.5)],
            'uint64Unpacked' => [new Repeated(uint64Unpacked: [new Number(1), new Number(2)]), "\x68\x01\x68\x02"],
            // non-packable types: a record per element regardless of the option
            'stringDefault' => [new Repeated(stringDefault: ['a', 'b']), "\x72\x01a\x72\x01b"],
            'stringUnpacked' => [new Repeated(stringUnpacked: ['a', 'b']), "\x7A\x01a\x7A\x01b"],
            'bytesUnpacked' => [new Repeated(bytesUnpacked: ["\x01"]), "\x82\x01\x01\x01"],
            'messageUnpacked' => [new Repeated(messageUnpacked: [new Inner(1)]), "\x8A\x01\x02\x08\x01"],
        ];

        foreach ($expected as $property => [$message, $bytes]) {
            $actual = $encoder->encode($message);

            if ($actual !== $bytes) {
                throw new LogicException(sprintf('%s: expected %s on the wire, got %s', $property, bin2hex($bytes), bin2hex($actual)));
            }
        }
    },
];
