<?php

declare(strict_types=1);

/**
 * Populated messages of the required_message snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Proto\Required\V1\Inner;
use Proto\Required\V1\Request;

return [
    'required and optional message set' => new Request(
        requiredInner: new Inner('required'),
        optionalInner: new Inner(),
    ),
    'required message set to an empty message' => new Request(new Inner()),
];
