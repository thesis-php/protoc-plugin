<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\Literal;
use Thesis\Google\Protobuf\FieldDescriptorProto\Type;
use Thesis\Protoc\Plugin\Dependency;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Parser;

/**
 * @api
 */
final readonly class TypeDeclarationFactory
{
    public function __construct(
        private Dependency\Graph $graph,
    ) {}

    public function create(Parser\FieldDescriptor $field, RuntimeImports $runtime): TypeDeclaration
    {
        if ($field->typeName !== null) {
            $fieldType = $this->graph->get($field->typeName);

            $isEnum = $field->type === Type::TYPE_ENUM;

            $default = null;

            if ($isEnum && $fieldType->container instanceof Parser\EnumDescriptor) {
                $defaultCase = $fieldType->container->cases[0]->name ?? null;
                if ($defaultCase !== null) {
                    $default = new Literal(\sprintf(
                        '%s::%s',
                        $fieldType->fqcn,
                        Naming::secureEnumCase($defaultCase),
                    ));
                }
            }

            return new TypeDeclaration(
                phpType: $fieldType->fqcn,
                reflectionType: Literal::new($runtime->reflection($isEnum ? 'EnumT' : 'ObjectT'), [
                    new Literal("{$fieldType->fqcn}::class"),
                ]),
                nullable: !$isEnum,
                docType: $fieldType->fqcn,
                default: $default,
            );
        }

        return match ($type = $field->type) {
            Type::TYPE_FLOAT,
            Type::TYPE_DOUBLE => new TypeDeclaration(
                phpType: 'float',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_FLOAT => $runtime->reflection('FloatT::T'),
                    Type::TYPE_DOUBLE => $runtime->reflection('DoubleT::T'),
                }),
                default: 0,
            ),
            Type::TYPE_UINT64,
            Type::TYPE_FIXED64 => new TypeDeclaration(
                phpType: '\BcMath\Number',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_UINT64 => $runtime->reflection('Uint64T::T'),
                    Type::TYPE_FIXED64 => $runtime->reflection('Fixed64T::T'),
                }),
                default: Literal::new('\BcMath\Number', [0]),
            ),
            Type::TYPE_INT32,
            Type::TYPE_UINT32,
            Type::TYPE_FIXED32,
            Type::TYPE_SFIXED32,
            Type::TYPE_SINT32,
            Type::TYPE_INT64,
            Type::TYPE_SFIXED64,
            Type::TYPE_SINT64 => new TypeDeclaration(
                phpType: 'int',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_INT32 => $runtime->reflection('Int32T::T'),
                    Type::TYPE_FIXED32 => $runtime->reflection('Fixed32T::T'),
                    Type::TYPE_UINT32 => $runtime->reflection('Uint32T::T'),
                    Type::TYPE_SFIXED32 => $runtime->reflection('SFixed32T::T'),
                    Type::TYPE_SINT32 => $runtime->reflection('SInt32T::T'),
                    Type::TYPE_INT64 => $runtime->reflection('Int64T::T'),
                    Type::TYPE_SFIXED64 => $runtime->reflection('SFixed64T::T'),
                    Type::TYPE_SINT64 => $runtime->reflection('SInt64T::T'),
                }),
                default: 0,
            ),
            Type::TYPE_BOOL => new TypeDeclaration(
                phpType: 'bool',
                reflectionType: new Literal($runtime->reflection('BoolT::T')),
                default: false,
            ),
            Type::TYPE_STRING,
            Type::TYPE_BYTES => new TypeDeclaration(
                phpType: 'string',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_STRING => $runtime->reflection('StringT::T'),
                    Type::TYPE_BYTES => $runtime->reflection('BytesT::T'),
                }),
                default: '',
            ),
            default => throw new \RuntimeException('No suitable declaration found.'),
        };
    }
}
