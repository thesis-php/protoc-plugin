<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final readonly class ImportDescriptor
{
    public function __construct(
        public string $name,
        public string $path,
    ) {}
}
