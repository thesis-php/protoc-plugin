<?php

declare(strict_types=1);

/**
 * Populated messages of the namespace_mapping snapshot case (types relocated by map.json), keyed by label
 * (see GeneratedCodeTest).
 */

use Thesis\Google\Protobuf\Timestamp;
use Thesis\Google\Rpc\Status;

return [
    'all fields set' => new Status(
        code: 5,
        kind: Status\Kind::KIND_RETRYABLE,
        detail: new Status\Detail('reason'),
        createdAt: new Timestamp(seconds: 1, nanos: 2),
        payload: new Status\PayloadStructured(new Status\Detail('')),
    ),
    'oneof variant with zero value' => new Status(payload: new Status\PayloadText('')),
];
