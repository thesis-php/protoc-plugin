<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Type;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;
use Thesis\Protoc\Plugin\FieldDescriptor;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\ProtoTypeDeclaration;
use Thesis\Protoc\Plugin\ProtoTypeDeclarationResolver;

/**
 * @api
 */
final readonly class UserProtoTypeDeclarationResolver implements ProtoTypeDeclarationResolver
{
    #[\Override]
    public function resolveProtoType(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ?ProtoTypeDeclaration {
        if ($field->typeName === null) {
            return null;
        }

        if (!\in_array($field->type, [Type::TYPE_MESSAGE, Type::TYPE_ENUM], true)) {
            return null;
        }

        if (str_starts_with($field->typeName, '.google.protobuf')) {
            return null;
        }

        $phpType = Naming::namespace($field->typeName);
        $relativeName = $namespace->simplifyType($phpType);

        $uses = [];

        if (!str_starts_with(ltrim($phpType, '\\'), $namespace->getName())) {
            $uses[] = $phpType;
            $relativeName = Naming::extract($relativeName, -1);
        }

        return new ProtoTypeDeclaration(
            phpType: $phpType,
            reflectionType: Literal::new('Reflection\\' . ($field->type === Type::TYPE_MESSAGE ? 'ObjectT' : 'EnumT'), [
                new Literal("{$relativeName}::class"),
            ]),
            uses: $uses,
            nullable: true,
            docType: $relativeName,
        );
    }
}
