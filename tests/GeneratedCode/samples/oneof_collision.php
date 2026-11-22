<?php

declare(strict_types=1);

/**
 * Populated messages of the oneof_collision snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Oneof\Collision\Document;
use Oneof\Collision\Envelope;
use Oneof\Collision\Event;
use Oneof\Collision\Task;
use Oneof\Collision\Wrapper;

return [
    'renamed oneof set to the nested message of the same name' => new Event(
        payload: new Event\PayloadP(new Event\Payload('a')),
        kind: new Event\KindNumber(42),
        payloadOneof: 'optional',
    ),
    'renamed oneof set to a scalar, oneof keeping its name set' => new Event(
        payload: new Event\PayloadS('s'),
        kind: new Event\KindFlag(true),
    ),
    'renamed oneof set to the nested enum of the same name' => new Task(
        state: new Task\StateKnown(Task\State::STATE_DONE),
    ),
    'oneof renamed with a numeric suffix past a nested message' => new Envelope(
        body: new Envelope\BodyWrapped(new Envelope\BodyOneof('wrapped')),
    ),
    'oneof renamed with a numeric suffix past a variant' => new Document(
        content: new Document\ContentOneof(new Document\Content('text')),
    ),
    'oneofs renamed past names differing in case and underscores' => new Wrapper(
        myPayload: new Wrapper\MyPayloadValue(new Wrapper\MyPayload('a')),
        kind: new Wrapper\KindTyped(new Wrapper\KIND('typed')),
    ),
];
