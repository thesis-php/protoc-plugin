<?php

declare(strict_types=1);

/**
 * Populated messages of the property_names snapshot case, keyed by label (see GeneratedCodeTest).
 */

use Property\Names\Fields;
use Property\Names\ThisOneof;
use Property\Names\Variants;

return [
    'fields named after a valid json_name, the field name and this_' => new Fields(
        this_: 'this',
        dashed: 'my-field',
        withSpace: 'with space',
        first: '1st',
        typeUrl: '@type',
        привет: 'привет',
        renamed: 'renamed',
        class: 'class',
        list: 'list',
        static: 'static',
        commentedField: 'commented',
        repeatedValues: [1, -2, 3],
        _1st_place: '_1st_place',
    ),
    'oneof this set to a variant with an invalid json_name' => new ThisOneof(
        this_: new ThisOneof\ThisNumber(number: 42),
    ),
    'oneof this set to a variant with a default json_name' => new ThisOneof(
        this_: new ThisOneof\ThisText(text: 'text'),
    ),
    'variant holding the field this' => new Variants(
        payload: new Variants\PayloadThis(this_: 'this'),
    ),
    'variant with an invalid json_name' => new Variants(
        payload: new Variants\PayloadRawValue(rawValue: 'raw'),
    ),
    'variant with a valid custom json_name' => new Variants(
        payload: new Variants\PayloadKept(Kept: 'kept'),
    ),
];
