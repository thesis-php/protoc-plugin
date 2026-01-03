<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\OneofOptions;

/**
 * @api
 */
final readonly class OneOfDescriptor
{
    public function __construct(
        public string $name,
        public ?OneofOptions $options = null,
    ) {}
}
