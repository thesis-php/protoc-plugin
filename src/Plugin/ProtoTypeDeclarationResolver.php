<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\PhpNamespace;

/**
 * @api
 */
interface ProtoTypeDeclarationResolver
{
    public function resolveProtoType(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ?ProtoTypeDeclaration;
}
