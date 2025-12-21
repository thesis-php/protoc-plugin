<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\EnumValueOptions;

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
