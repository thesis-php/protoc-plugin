<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\FileDescriptorProto;
use Thesis\Protobuf\Compiler\FileOptions;

/**
 * @api
 */
final readonly class FileDescriptor
{
    /**
     * @param list<MessageDescriptor> $messages
     * @param list<EnumDescriptor> $enums
     */
    public function __construct(
        public FileDescriptorProto $file,
        public array $messages = [],
        public array $enums = [],
        public ?string $package = null,
        public ?FileOptions $options = null,
        public ?Comment $packageComments = null,
        public ?Comment $syntaxComments = null,
        public ?Comment $editionComments = null,
        public ?string $syntax = null,
    ) {}
}
