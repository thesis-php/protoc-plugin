<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final readonly class GrpcTypeDeclaration
{
    public function __construct(
        public string $name,
        public string $use,
    ) {}
}
