<?php

declare(strict_types=1);

/**
 * Populated messages of the enum_alias snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Test\Alias\V1\Job;
use Test\Alias\V1\Job\Kind;
use Test\Alias\V1\Status;

return [
    'enum fields set through aliases' => new Job(
        status: Status::STATUS_UNSPECIFIED,
        aliased: Status::STATUS_RUNNING,
        requiredAliased: Status::STATUS_IN_PROGRESS,
        kind: Kind::class_,
        kindAliased: Kind::KIND_BULK,
        history: [Status::STATUS_UNSPECIFIED, Status::STATUS_RUNNING, Status::STATUS_IN_PROGRESS],
    ),
    'enum fields set through canonical cases' => new Job(
        status: Status::STATUS_UNKNOWN,
        aliased: Status::STATUS_DONE,
        requiredAliased: Status::STATUS_STARTED,
        kind: Kind::object,
        kindAliased: Kind::KIND_BATCH,
        history: [Status::STATUS_UNKNOWN, Status::STATUS_STARTED, Status::STATUS_DONE],
    ),
    'defaults given by aliases' => new Job(),
];
