<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\EnumValueOptions;
use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class EnumCaseDescriptor
{
    public function __construct(
        public string $name,
        public int $value,
        public ?Comment $comment = null,
        public ?EnumValueOptions $options = null,
    ) {}
}
