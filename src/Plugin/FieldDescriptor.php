<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\FieldDescriptorProto\Label;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;
use Thesis\Protobuf\Compiler\FieldOptions;

/**
 * @api
 */
final readonly class FieldDescriptor
{
    public function __construct(
        public string $name,
        public int $number,
        public ?Label $label = null,
        public ?Type $type = null,
        public ?string $typeName = null,
        public ?Comment $comment = null,
        public ?FieldOptions $options = null,
        public bool $optional = false,
        public ?int $oneOfIndex = null,
        public ?string $defaultValue = null,
    ) {}
}
