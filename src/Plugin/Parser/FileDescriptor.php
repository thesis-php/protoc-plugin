<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\FileDescriptorProto;
use Google\Protobuf\FileOptions;
use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class FileDescriptor
{
    /**
     * @param non-empty-string $name
     * @param list<MessageDescriptor> $messages
     * @param list<EnumDescriptor> $enums
     * @param list<ServiceDescriptor> $services
     * @param list<string> $dependencies
     */
    public function __construct(
        public string $name,
        public FileDescriptorProto $file,
        public array $messages = [],
        public array $enums = [],
        public array $services = [],
        public array $dependencies = [],
        public ?string $package = null,
        public ?FileOptions $options = null,
        public ?Comment $packageComments = null,
        public ?Comment $syntaxComments = null,
        public ?Comment $editionComments = null,
        public ?string $syntax = null,
    ) {}
}
