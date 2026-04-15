<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\FeatureSet;
use Google\Protobuf\FieldDescriptorProto\Label;
use Google\Protobuf\FieldDescriptorProto\Type;
use Google\Protobuf\FieldOptions;
use Thesis\Protoc\Plugin\Comment;

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
        public ?bool $proto3Optional = null,
        public ?int $oneOfIndex = null,
        public ?string $defaultValue = null,
        public ?MapDescriptor $map = null,
        public ?FeatureSet $features = null,
    ) {}
}
