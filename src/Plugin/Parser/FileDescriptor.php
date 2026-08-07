<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\FileDescriptorProto;
use Google\Protobuf\FileOptions;
use Thesis\Protoc\Plugin\Comment;
use Thesis\Protoc\Plugin\Naming;

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

    /**
     * The class names generated directly in the file's namespace — the top-level
     * messages and enums the descriptor registry must not clash with.
     *
     * @return array<string, true>
     */
    public function topLevelClassNames(): array
    {
        $names = [];

        foreach ($this->messages as $message) {
            $names[Naming::pascalCase($message->name)] = true;
        }

        foreach ($this->enums as $enum) {
            $names[Naming::pascalCase($enum->name)] = true;
        }

        return $names;
    }
}
