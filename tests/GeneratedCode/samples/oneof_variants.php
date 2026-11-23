<?php

declare(strict_types=1);

/**
 * Samples of the oneof_variants snapshot case, keyed by label (see GeneratedCodeTest): every variant built with its
 * default value, which is written as the variant is set, and a check of the defaults the variants are declared with.
 */

use BcMath\Number;
use Oneof\Variants\Editions;
use Oneof\Variants\Proto2;

$samples = [];

foreach ([
    Proto2\Choice\ValueNumber::class,
    Proto2\Choice\ValueText::class,
    Proto2\Choice\ValueRaw::class,
    Proto2\Choice\ValueFlag::class,
    Proto2\Choice\ValueBig::class,
    Proto2\Choice\ValueRatio::class,
    Proto2\Choice\ValueLevel::class,
] as $variant) {
    $samples["proto2 variant {$variant} with its default value"] = new Proto2\Choice(value: new $variant());
}

foreach ([
    Proto2\Choice\DefaultedNumberDefault::class,
    Proto2\Choice\DefaultedTextDefault::class,
    Proto2\Choice\DefaultedRawDefault::class,
    Proto2\Choice\DefaultedRatioDefault::class,
    Proto2\Choice\DefaultedLevelDefault::class,
] as $variant) {
    $samples["proto2 variant {$variant} with its declared default"] = new Proto2\Choice(defaulted: new $variant());
}

foreach ([
    Editions\Choice\ValueNumber::class,
    Editions\Choice\ValueText::class,
    Editions\Choice\ValueLevel::class,
] as $variant) {
    $samples["editions variant {$variant} with its default value"] = new Editions\Choice(value: new $variant());
}

foreach ([
    Editions\Choice\DefaultedNumberDefault::class,
    Editions\Choice\DefaultedTextDefault::class,
    Editions\Choice\DefaultedLevelDefault::class,
] as $variant) {
    $samples["editions variant {$variant} with its declared default"] = new Editions\Choice(defaulted: new $variant());
}

return [
    ...$samples,
    'proto2 variants set' => new Proto2\Choice(
        value: new Proto2\Choice\ValueInner(new Proto2\Inner(7)),
        defaulted: new Proto2\Choice\DefaultedLevelDefault(Proto2\Level::LEVEL_LOW),
    ),
    'proto2 uint64 variant set' => new Proto2\Choice(value: new Proto2\Choice\ValueBig(new Number('18446744073709551615'))),
    'editions variants set' => new Editions\Choice(
        value: new Editions\Choice\ValueInner(new Editions\Inner(-1)),
        defaulted: new Editions\Choice\DefaultedNumberDefault(0),
    ),
    'variants take the declared defaults' => static function (): void {
        $expected = [
            'proto2 number' => [new Proto2\Choice\ValueNumber()->number, 0],
            'proto2 level' => [new Proto2\Choice\ValueLevel()->level, Proto2\Level::LEVEL_LOW],
            'proto2 numberDefault' => [new Proto2\Choice\DefaultedNumberDefault()->numberDefault, 42],
            'proto2 textDefault' => [new Proto2\Choice\DefaultedTextDefault()->textDefault, 'hello'],
            'proto2 rawDefault' => [new Proto2\Choice\DefaultedRawDefault()->rawDefault, "\x01\xff"],
            'proto2 ratioDefault' => [new Proto2\Choice\DefaultedRatioDefault()->ratioDefault, INF],
            'proto2 levelDefault' => [new Proto2\Choice\DefaultedLevelDefault()->levelDefault, Proto2\Level::LEVEL_HIGH],
            'editions numberDefault' => [new Editions\Choice\DefaultedNumberDefault()->numberDefault, 42],
            'editions textDefault' => [new Editions\Choice\DefaultedTextDefault()->textDefault, 'hello'],
            'editions levelDefault' => [new Editions\Choice\DefaultedLevelDefault()->levelDefault, Editions\Level::LEVEL_HIGH],
        ];

        // Bytes are printed escaped, the failure is reported as JSON.
        $show = static fn(mixed $value): string => is_string($value) ? '"' . addcslashes($value, "\0..\37\"\\\177..\377") . '"' : var_export($value, true);
        $mismatches = [];

        foreach ($expected as $variant => [$actual, $value]) {
            if ($actual !== $value) {
                $mismatches[] = sprintf('%s: expected %s, got %s', $variant, $show($value), $show($actual));
            }
        }

        if ($mismatches !== []) {
            throw new LogicException(implode('; ', $mismatches) . '.');
        }
    },
];
