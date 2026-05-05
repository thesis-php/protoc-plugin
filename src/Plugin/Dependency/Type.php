<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Dependency;

use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class Type
{
    public function __construct(
        public string $fqcn,
        public string $class,
        public Parser\EnumDescriptor|Parser\MessageDescriptor $container,
    ) {}
}
