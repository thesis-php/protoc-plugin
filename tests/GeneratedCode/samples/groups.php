<?php

declare(strict_types=1);

/**
 * Samples of the groups snapshot case, keyed by label (see GeneratedCodeTest): populated messages and a check that
 * groups are written between a start and an end group tag (wire types 3 and 4), message fields and maps after their
 * length. The expected bytes are those protoc 32.1 writes for the text format in the comments
 * (`protoc --encode=<type> <file>.proto`); they are also decoded back into the message. protoc writes the fields
 * in the order of their numbers, the encoder required fields first and oneofs last, so the fixture numbers the
 * fields in that order for whole messages to be compared byte for byte.
 */

use Groups\Editions\Field\V1 as Field;
use Groups\Editions\File\V1 as File;
use Groups\Proto2\V1 as Proto2;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Map;

$searchResponse = new Proto2\SearchResponse(
    result: new Proto2\SearchResponse\Result(url: 'a', rank: 1),
    item: [
        new Proto2\SearchResponse\Item(id: 1, tag: new Proto2\SearchResponse\Item\Tag(name: 'x')),
        new Proto2\SearchResponse\Item(id: 2),
        new Proto2\SearchResponse\Item(),
    ],
    best: new Proto2\SearchResponse\Result(url: 'b'),
    others: [new Proto2\SearchResponse\Result(rank: 2)],
    choice: new Proto2\SearchResponse\ChoicePicked(new Proto2\SearchResponse\Picked(id: 3)),
);

$delimited = new Field\Delimited(
    required: new Field\Inner(1),
    plain: new Field\Inner(2),
    single: new Field\Inner(3),
    list: [new Field\Inner(4), new Field\Inner()],
    choice: new Field\Delimited\ChoiceVariant(new Field\Inner(5)),
);

$tree = new File\Tree(
    single: new File\Leaf(1),
    list: [new File\Leaf(2), new File\Leaf()],
    byName: Map::fromArray(['k' => new File\Leaf(4)]),
    plain: new File\Leaf(5),
    child: new File\Tree(single: new File\Leaf(6), child: new File\Tree()),
    choice: new File\Tree\ChoiceVariant(new File\Leaf(7)),
);

return [
    'proto2 groups set' => $searchResponse,
    'proto2 required group' => new Proto2\RequiredGroup(new Proto2\RequiredGroup\Header(version: 1)),
    'editions DELIMITED fields set' => $delimited,
    'editions DELIMITED file default' => $tree,
    'groups are written between start and end group tags' => static function () use ($searchResponse, $delimited, $tree): void {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = Decoder\Builder::buildDefault();

        $expected = [
            // Result{url:"a" rank:1} Item{id:1 Tag{name:"x"}} Item{id:2} Item{} Picked{id:3} best{url:"b"} others{rank:2}
            'proto2 groups' => [
                $searchResponse,
                '0b 12 01 61 18 01 0c 23 28 01 33 3a 01 78 34 24 23 28 02 24 23 24 42 03 12 01 62 4a 02 18 02 53 58 03 54',
            ],
            // Result{} none:"n"
            'proto2 empty group, string variant' => [
                new Proto2\SearchResponse(
                    result: new Proto2\SearchResponse\Result(),
                    choice: new Proto2\SearchResponse\ChoiceNone('n'),
                ),
                '0b 0c 62 01 6e',
            ],
            // Picked{}
            'proto2 empty group variant' => [
                new Proto2\SearchResponse(choice: new Proto2\SearchResponse\ChoicePicked(new Proto2\SearchResponse\Picked())),
                '53 54',
            ],
            // Header{version:1}
            'proto2 required group' => [new Proto2\RequiredGroup(new Proto2\RequiredGroup\Header(version: 1)), '0b 10 01 0c'],
            // Header{}
            'proto2 empty required group' => [new Proto2\RequiredGroup(new Proto2\RequiredGroup\Header()), '0b 0c'],
            // required{value:1} plain{value:2} single{value:3} list{value:4} list{} variant{value:5}
            'editions DELIMITED fields' => [
                $delimited,
                '0b 08 01 0c 12 02 08 02 1b 08 03 1c 23 08 04 24 23 24 2b 08 05 2c',
            ],
            // required{} variant_plain{value:6}
            'editions empty required group, length-prefixed variant' => [
                new Field\Delimited(
                    required: new Field\Inner(),
                    choice: new Field\Delimited\ChoiceVariantPlain(new Field\Inner(6)),
                ),
                '0b 0c 32 02 08 06',
            ],
            // single{value:1} list{value:2} list{} by_name{key:"k" value{value:4}} plain{value:5} child{single{value:6} child{}} variant{value:7}
            'editions DELIMITED file default' => [
                $tree,
                '0b 08 01 0c 13 08 02 14 13 14 1a 07 0a 01 6b 12 02 08 04 22 02 08 05 2b 0b 08 06 0c 2b 2c 2c 33 08 07 34',
            ],
            // variant{}
            'editions empty group variant' => [new File\Tree(choice: new File\Tree\ChoiceVariant(new File\Leaf())), '33 34'],
        ];

        foreach ($expected as $label => [$message, $hex]) {
            $bytes = hex2bin(str_replace(' ', '', $hex));
            $actual = $encoder->encode($message);

            if ($actual !== $bytes) {
                throw new LogicException(sprintf('%s: expected %s on the wire, got %s', $label, bin2hex($bytes), bin2hex($actual)));
            }

            // Compared by structure: the decoded message is another object.
            $decoded = var_export($decoder->decode($bytes, $message::class), true);
            $original = var_export($message, true);

            if ($decoded !== $original) {
                throw new LogicException(sprintf('%s: decoded %s, expected %s', $label, $decoded, $original));
            }
        }
    },
];
