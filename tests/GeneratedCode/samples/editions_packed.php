<?php

declare(strict_types=1);

/**
 * Samples of the editions_packed snapshot case, keyed by label (see GeneratedCodeTest): populated messages and a check
 * of the wire encoding every repeated field is written in.
 */

use BcMath\Number;
use Editions\Packed\V1\Color;
use Editions\Packed\V1\Inner;
use Editions\Packed\V1\Repeated;
use Editions\Packed\V1\RepeatedExpanded;
use Thesis\Protobuf\Encoder;

return [
    'all fields set' => new Repeated(
        int32Default: [1, -2, 3],
        int32Expanded: [-6, 7],
        enumDefault: [Color::COLOR_RED, Color::COLOR_GREEN],
        enumExpanded: [Color::COLOR_RED, Color::COLOR_UNSPECIFIED],
        boolDefault: [true, false],
        boolExpanded: [true, true, false],
        sint64Default: [-1, 1],
        fixed32Default: [1, 4_294_967_295],
        doubleDefault: [1.5, -0.25],
        uint64Default: [new Number(1), new Number('18446744073709551615')],
        uint64Expanded: [new Number(0), new Number(2)],
        stringDefault: ['a', ''],
        stringExpanded: ['b'],
        bytesDefault: ["\x00\xff", ''],
        bytesExpanded: ["\x01"],
        messageDefault: [new Inner(1), new Inner()],
        messageExpanded: [new Inner(-1)],
    ),
    'all fields set, file default expanded' => new RepeatedExpanded(
        int32Default: [1, -2, 3],
        int32Packed: [4, 5],
        enumDefault: [Color::COLOR_GREEN],
        enumPacked: [Color::COLOR_RED, Color::COLOR_UNSPECIFIED],
        doubleDefault: [0.5],
        doublePacked: [1.5, -0.25],
        stringDefault: ['a', ''],
        bytesDefault: ["\x00\xff"],
        messageDefault: [new Inner(1), new Inner()],
    ),
    'wire encoding follows the repeated_field_encoding feature' => static function (): void {
        $encoder = Encoder\Builder::buildDefault();

        $expected = [
            // PACKED by default: one length-delimited record
            'int32Default' => [new Repeated(int32Default: [1, 2]), "\x0A\x02\x01\x02"],
            'enumDefault' => [new Repeated(enumDefault: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x1A\x02\x01\x02"],
            'boolDefault' => [new Repeated(boolDefault: [true, false]), "\x2A\x02\x01\x00"],
            'sint64Default' => [new Repeated(sint64Default: [-1, 1]), "\x3A\x02\x01\x02"],
            'fixed32Default' => [new Repeated(fixed32Default: [1, 2]), "\x42\x08\x01\x00\x00\x00\x02\x00\x00\x00"],
            'doubleDefault' => [new Repeated(doubleDefault: [1.5]), "\x4A\x08" . pack('e', 1.5)],
            'uint64Default' => [new Repeated(uint64Default: [new Number(1), new Number(2)]), "\x52\x02\x01\x02"],
            // EXPANDED on the field: a record per element
            'int32Expanded' => [new Repeated(int32Expanded: [1, 2]), "\x10\x01\x10\x02"],
            'enumExpanded' => [new Repeated(enumExpanded: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x20\x01\x20\x02"],
            'boolExpanded' => [new Repeated(boolExpanded: [true, false]), "\x30\x01\x30\x00"],
            'uint64Expanded' => [new Repeated(uint64Expanded: [new Number(1), new Number(2)]), "\x58\x01\x58\x02"],
            // non-packable types: a record per element whatever the feature resolves to
            'stringDefault' => [new Repeated(stringDefault: ['a', 'b']), "\x62\x01a\x62\x01b"],
            'stringExpanded' => [new Repeated(stringExpanded: ['a', 'b']), "\x6A\x01a\x6A\x01b"],
            'bytesDefault' => [new Repeated(bytesDefault: ["\x01"]), "\x72\x01\x01"],
            'bytesExpanded' => [new Repeated(bytesExpanded: ["\x01"]), "\x7A\x01\x01"],
            'messageDefault' => [new Repeated(messageDefault: [new Inner(1)]), "\x82\x01\x02\x08\x01"],
            'messageExpanded' => [new Repeated(messageExpanded: [new Inner(1)]), "\x8A\x01\x02\x08\x01"],
            // file default EXPANDED: a record per element unless the field is set to PACKED
            'expanded int32Default' => [new RepeatedExpanded(int32Default: [1, 2]), "\x08\x01\x08\x02"],
            'expanded int32Packed' => [new RepeatedExpanded(int32Packed: [1, 2]), "\x12\x02\x01\x02"],
            'expanded enumDefault' => [new RepeatedExpanded(enumDefault: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x18\x01\x18\x02"],
            'expanded enumPacked' => [new RepeatedExpanded(enumPacked: [Color::COLOR_RED, Color::COLOR_GREEN]), "\x22\x02\x01\x02"],
            'expanded doubleDefault' => [new RepeatedExpanded(doubleDefault: [1.5]), "\x29" . pack('e', 1.5)],
            'expanded doublePacked' => [new RepeatedExpanded(doublePacked: [1.5]), "\x32\x08" . pack('e', 1.5)],
            'expanded stringDefault' => [new RepeatedExpanded(stringDefault: ['a', 'b']), "\x3A\x01a\x3A\x01b"],
            'expanded bytesDefault' => [new RepeatedExpanded(bytesDefault: ["\x01"]), "\x42\x01\x01"],
            'expanded messageDefault' => [new RepeatedExpanded(messageDefault: [new Inner(1)]), "\x4A\x02\x08\x01"],
        ];

        foreach ($expected as $property => [$message, $bytes]) {
            $actual = $encoder->encode($message);

            if ($actual !== $bytes) {
                throw new LogicException(sprintf('%s: expected %s on the wire, got %s', $property, bin2hex($bytes), bin2hex($actual)));
            }
        }
    },
];
