<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Thesis\Protoc\Plugin\Dependency;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class TypeDeclarationFactory
{
    private NativeTypeDeclarationFactory $native;

    private ComplexTypeDeclarationFactory $complex;

    public function __construct(Dependency\Graph $graph)
    {
        $this->native = new NativeTypeDeclarationFactory();
        $this->complex = new ComplexTypeDeclarationFactory($graph);
    }

    public function create(Parser\FieldDescriptor $field): TypeDeclaration
    {
        if ($field->typeName === null) {
            return $this->native->create($field);
        }

        return $this->complex->create($field, $field->typeName);
    }
}
