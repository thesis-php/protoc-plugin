<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\PhpNamespace;

/**
 * @api
 */
interface TypeDeclarationResolver
{
    public function supports(FieldDescriptor $field): bool;

    public function resolve(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): TypeDeclaration;
}
