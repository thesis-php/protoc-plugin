<?php

declare(strict_types=1);

/**
 * Populated messages of the proto3 snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Proto\Api\V1\Kind;
use Proto\Api\V1\TestRequest;

return [
    'all fields set' => new TestRequest(
        stringValue: 'value',
        optionalStringValue: 'set',
        implicitKind: Kind::KIND_SECOND,
        optionalKind: Kind::KIND_FIRST,
        contact: new TestRequest\ContactEmail('user@example.com'),
    ),
    'explicit presence keeps zero values' => new TestRequest(
        optionalStringValue: '',
        optionalKind: Kind::KIND_UNSPECIFIED,
        contact: new TestRequest\ContactPhone(''),
    ),
];
