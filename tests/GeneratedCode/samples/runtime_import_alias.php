<?php

declare(strict_types=1);

/**
 * Populated messages of the runtime_import_alias snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Runtime\Alias\Field;
use Runtime\Alias\Holder;
use Runtime\Alias\Item;
use Runtime\Alias\Kind;
use Runtime\Alias\Map;
use Runtime\Alias\OneOf;
use Runtime\Alias\Outer;
use Runtime\Alias\Protobuf;
use Runtime\Alias\Reflection;
use Runtime\Alias\StringT;
use Runtime\Alias\Variants;
use Thesis\Protobuf\Map as RuntimeMap;

return [
    'message named after the Reflection alias, oneof set to a scalar' => new Reflection(
        scalar: 'scalar',
        numbers: [1, -2, 3],
        kind: Kind::KIND_ONE,
        item: new Item('item'),
        items: RuntimeMap::fromArray(['a' => new Item('a'), 'b' => new Item('b')]),
        choice: new Reflection\ChoiceText('text'),
    ),
    'message named after the Reflection alias, oneof set to a message' => new Reflection(
        choice: new Reflection\ChoiceObject(new Item('object')),
    ),
    'message named after the Protobuf alias' => new Protobuf(
        counts: RuntimeMap::fromArray(['one' => 1, 'two' => 2]),
        name: 'protobuf',
    ),
    'nested message named after the Reflection alias' => new Outer(
        inner: new Outer\Reflection('a', RuntimeMap::fromArray([1 => 'one', 2 => 'two'])),
    ),
    'oneof variant differing from the Reflection alias in case' => new Variants(
        reflect: new Variants\ReflectIon('ion'),
    ),
    'oneof variant next to it' => new Variants(
        reflect: new Variants\ReflectOther(7),
    ),
    'messages named after runtime classes' => new Holder(
        reflection: new Reflection(scalar: 'r'),
        protobuf: new Protobuf(counts: RuntimeMap::fromArray(['c' => 3])),
        nested: new Outer\Reflection('n'),
        field: new Field('field'),
        map: new Map(RuntimeMap::fromArray(['k' => 'v'])),
        oneOf: new OneOf(new OneOf\ValueI(5)),
        stringT: new StringT('string'),
        list: [new Reflection(scalar: 'first'), new Reflection(kind: Kind::KIND_ONE)],
        byName: RuntimeMap::fromArray(['x' => new Reflection(numbers: [9])]),
    ),
];
