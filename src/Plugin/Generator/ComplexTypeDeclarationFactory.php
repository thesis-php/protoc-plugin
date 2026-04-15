<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Google\Protobuf\FieldDescriptorProto\Type;
use Nette\PhpGenerator\Literal;
use Thesis\Protoc\Plugin\Dependency;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class ComplexTypeDeclarationFactory
{
    public function __construct(
        private Dependency\Graph $graph,
    ) {}

    public function create(Parser\FieldDescriptor $field, string $typeName): TypeDeclaration
    {
        $fieldType = $this->graph->get($typeName);

        $isEnum = $field->type === Type::TYPE_ENUM;

        $default = null;

        if ($isEnum && $fieldType->default !== null) {
            $default = new Literal(\sprintf(
                '%s::%s',
                $fieldType->fqcn,
                Naming::secureEnumCase($fieldType->default),
            ));
        }

        return new TypeDeclaration(
            phpType: $fieldType->fqcn,
            reflectionType: Literal::new('Reflection\\' . ($isEnum ? 'EnumT' : 'ObjectT'), [
                new Literal("{$fieldType->fqcn}::class"),
            ]),
            nullable: !$isEnum,
            docType: $fieldType->fqcn,
            default: $default,
        );
    }
}
