<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;

/**
 * @api
 */
final readonly class CustomTypeDeclarationResolver implements TypeDeclarationResolver
{
    #[\Override]
    public function supports(FieldDescriptor $field): bool
    {
        return $field->typeName !== null
            && \in_array($field->type, [Type::TYPE_MESSAGE, Type::TYPE_ENUM], true)
            && !str_starts_with($field->typeName, '.google.protobuf');
    }

    #[\Override]
    public function resolve(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): TypeDeclaration {
        \assert($field->typeName !== null);

        $typeName = Naming::namespace($field->typeName);

        return new TypeDeclaration(
            phpType: $typeName,
            reflectionType: Literal::new('Reflection\\' . ($field->type === Type::TYPE_MESSAGE ? 'ObjectT' : 'EnumT'), [
                new Literal("{$namespace->simplifyName($typeName)}::class"),
            ]),
            nullable: true,
            docType: $namespace->simplifyName($typeName),
        );
    }
}
