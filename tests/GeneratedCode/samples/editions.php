<?php

declare(strict_types=1);

/**
 * Populated messages of the editions snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Proto\Api\V1\EditionsFeatures;
use Proto\Api\V1\Inner;
use Proto\Api\V1\Type;

return [
    'all fields set' => new EditionsFeatures(
        legacyRequiredBare: -7,
        implicit: 'implicit',
        explicit: '',
        legacyRequired: 'required',
        defaultPresence: 'present',
        packed: [1, 2, 3],
        expanded: [-1, 0, 1],
        defaultEncoding: [2_147_483_647],
        type: Type::TYPE_PROTO3,
        implicitType: Type::TYPE_PROTO3,
        defaultType: Type::TYPE_UNSPECIFIED,
        inner: new Inner('inner'),
    ),
    'legacy required field set to zero value' => new EditionsFeatures(legacyRequiredBare: 0),
    'legacy required field with a default set to zero value' => new EditionsFeatures(
        legacyRequiredBare: 1,
        legacyRequired: '',
    ),
];
