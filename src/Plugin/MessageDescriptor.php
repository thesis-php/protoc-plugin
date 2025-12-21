<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\MessageOptions;

/**
 * @api
 */
final readonly class MessageDescriptor
{
    /**
     * @param list<FieldDescriptor> $fields
     * @param list<EnumDescriptor> $enums
     * @param list<self> $messages
     */
    public function __construct(
        public string $name,
        public string $path,
        public array $fields = [],
        public array $enums = [],
        public array $messages = [],
        public ?Comment $comment = null,
        public ?MessageOptions $options = null,
    ) {}
}
