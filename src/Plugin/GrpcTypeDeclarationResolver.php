<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
interface GrpcTypeDeclarationResolver
{
    public function resolveGrpcType(string $fqcn): ?GrpcTypeDeclaration;
}
