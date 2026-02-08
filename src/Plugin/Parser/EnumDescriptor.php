<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\EnumOptions;
use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class EnumDescriptor
{
    /**
     * @param list<EnumCaseDescriptor> $cases
     */
    public function __construct(
        public string $name,
        public string $path,
        public array $cases,
        public ?Comment $comment = null,
        public ?EnumOptions $options = null,
    ) {}
}
