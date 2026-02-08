<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\Literal;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;
use Thesis\Protoc\Plugin\Dependency;
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

        return new TypeDeclaration(
            phpType: $fieldType->fqcn,
            reflectionType: Literal::new('Reflection\\' . ($field->type === Type::TYPE_MESSAGE ? 'ObjectT' : 'EnumT'), [
                new Literal("{$fieldType->fqcn}::class"),
            ]),
            nullable: true,
            docType: $fieldType->fqcn,
        );
    }
}
