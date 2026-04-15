<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

/**
 * @api
 */
final readonly class Type
{
    public function __construct(
        public string $fqcn,
        public string $class,
        public ?string $default = null,
    ) {}
}
