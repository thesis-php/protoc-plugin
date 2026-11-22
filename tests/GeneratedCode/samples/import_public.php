<?php

declare(strict_types=1);

/**
 * Populated messages of the import_public snapshot case (types visible only through "import public"), keyed by label
 * (see GeneratedCodeTest).
 */

use T\Pub\A\A;
use T\Pub\C\C;
use T\Pub\D\D;
use T\Pub\Legacy\Legacy;
use T\Pub\Re\Wrapper;
use Test\ImportPublic\Custom\Box;
use Test\ImportPublic\Custom\Color;
use Thesis\Protobuf\Map;

return [
    'all fields set' => new A(
        c: new C('c', new D('nested')),
        d: new D('d'),
        box: new Box(new Box\Item('in box'), Color::COLOR_RED),
        item: new Box\Item('item'),
        color: Color::COLOR_GREEN,
        byName: Map::fromArray(['first' => new D('1'), 'second' => new D('2')]),
        colors: Map::fromArray([1 => Color::COLOR_RED, 2 => Color::COLOR_GREEN]),
        list: [new C('x'), new C('y', new D('z'))],
        maybeColor: Color::COLOR_UNSPECIFIED,
    ),
    're-exporting file using the re-exported types' => new Wrapper(
        box: new Box(new Box\Item('wrapped')),
        color: Color::COLOR_RED,
        c: new C('c'),
        d: new D('d'),
    ),
    'enum defaults of a re-exported enum' => new Legacy(
        color: Color::COLOR_RED,
        requiredColor: Color::COLOR_GREEN,
    ),
];
