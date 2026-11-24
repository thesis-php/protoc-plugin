<?php

declare(strict_types=1);

/**
 * Populated messages of the php_namespace_verbatim snapshot case (a php_namespace with underscores and lowercase
 * letters, which nested types, oneof interfaces and variants must share with the top-level types), keyed by label
 * (see GeneratedCodeTest).
 */

use Thesis\Protobuf\Map;
use verbatim\Lower_Case\Envelope;
use Verbatim_Ns\under_score\Holder;
use Verbatim_Ns\under_score\Outer;

return [
    'nested types and oneof variants' => new Outer(
        inner: new Outer\Inner(new Outer\Inner\Deep('deep')),
        kind: Outer\Kind::KIND_ONE,
        choice: new Outer\ChoiceNested(new Outer\Inner(new Outer\Inner\Deep('variant'))),
    ),
    'enum oneof variant' => new Outer(choice: new Outer\ChoiceKnown(Outer\Kind::KIND_ONE)),
    'scalar oneof variant' => new Outer(choice: new Outer\ChoiceText('text')),
    'nested types referenced from another message' => new Holder(
        inner: new Outer\Inner(new Outer\Inner\Deep('a')),
        deep: new Outer\Inner\Deep('b'),
        kind: Outer\Kind::KIND_ONE,
        list: [new Outer\Inner(), new Outer\Inner(new Outer\Inner\Deep('c'))],
        byName: Map::fromArray(['d' => new Outer\Inner\Deep('d')]),
    ),
    'lowercase php_namespace referencing nested types of another file' => new Envelope(
        body: new Envelope\Body(new Outer\Inner(new Outer\Inner\Deep('body'))),
        payload: new Envelope\PayloadInlineBody(new Envelope\Body()),
    ),
    'lowercase php_namespace with an enum variant of another file' => new Envelope(
        payload: new Envelope\PayloadKind(Outer\Kind::KIND_ONE),
    ),
];
