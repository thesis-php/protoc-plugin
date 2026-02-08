<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Thesis\Protobuf\Compiler\MessageOptions;
use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class MessageDescriptor
{
    /**
     * @param list<FieldDescriptor> $fields
     * @param list<EnumDescriptor> $enums
     * @param list<self> $messages
     * @param list<OneOfDescriptor> $oneofs
     */
    public function __construct(
        public string $name,
        public string $path,
        public array $fields = [],
        public array $enums = [],
        public array $messages = [],
        public array $oneofs = [],
        public ?Comment $comment = null,
        public ?MessageOptions $options = null,
    ) {}
}
