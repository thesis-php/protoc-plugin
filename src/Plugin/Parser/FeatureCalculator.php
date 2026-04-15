<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\Edition;
use Google\Protobuf\FeatureSet;

/**
 * Calculates a {@see FeatureSet} for a given edition by merging edition defaults
 * with the inheritance chain file → message(s) → field. Mirrors the resolution
 * protoc itself performs for editions.
 *
 * @internal
 */
final readonly class FeatureCalculator
{
    /**
     * @param list<FeatureSet> $overrides ordered from outermost (file) to innermost (field)
     */
    public static function calculate(Edition $edition, array $overrides): FeatureSet
    {
        $set = new FeatureSet(
            fieldPresence: match ($edition) {
                Edition::EDITION_PROTO3 => FeatureSet\FieldPresence::IMPLICIT,
                default => FeatureSet\FieldPresence::EXPLICIT,
            },
            enumType: match ($edition) {
                Edition::EDITION_PROTO2 => FeatureSet\EnumType::CLOSED,
                default => FeatureSet\EnumType::OPEN,
            },
            repeatedFieldEncoding: match ($edition) {
                Edition::EDITION_PROTO2 => FeatureSet\RepeatedFieldEncoding::EXPANDED,
                default => FeatureSet\RepeatedFieldEncoding::PACKED,
            },
            utf8Validation: match ($edition) {
                Edition::EDITION_PROTO2 => FeatureSet\Utf8Validation::NONE,
                default => FeatureSet\Utf8Validation::VERIFY,
            },
            messageEncoding: FeatureSet\MessageEncoding::LENGTH_PREFIXED,
            jsonFormat: match ($edition) {
                Edition::EDITION_PROTO2 => FeatureSet\JsonFormat::LEGACY_BEST_EFFORT,
                default => FeatureSet\JsonFormat::ALLOW,
            },
        );

        foreach ($overrides as $override) {
            $set = new FeatureSet(
                fieldPresence: $override->fieldPresence ?? $set->fieldPresence,
                enumType: $override->enumType ?? $set->enumType,
                repeatedFieldEncoding: $override->repeatedFieldEncoding ?? $set->repeatedFieldEncoding,
                utf8Validation: $override->utf8Validation ?? $set->utf8Validation,
                messageEncoding: $override->messageEncoding ?? $set->messageEncoding,
                jsonFormat: $override->jsonFormat ?? $set->jsonFormat,
            );
        }

        return $set;
    }
}
