<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;

/**
 * @api
 */
final readonly class NativeTypeDeclarationResolver implements TypeDeclarationResolver
{
    #[\Override]
    public function supports(FieldDescriptor $field): bool
    {
        return $field->typeName === null && $field->type !== null && !\in_array($field->type, [Type::TYPE_ENUM, Type::TYPE_MESSAGE, Type::TYPE_GROUP], true);
    }

    #[\Override]
    public function resolve(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): TypeDeclaration {
        return match ($type = $field->type) {
            Type::TYPE_FLOAT,
            Type::TYPE_DOUBLE => new TypeDeclaration(
                phpType: 'float',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_FLOAT => 'Reflection\FloatT::T',
                    Type::TYPE_DOUBLE => 'Reflection\DoubleT::T',
                }),
                default: 0,
            ),
            Type::TYPE_INT64,
            Type::TYPE_UINT64,
            Type::TYPE_FIXED64,
            Type::TYPE_SFIXED64,
            Type::TYPE_SINT64 => new TypeDeclaration(
                phpType: Number::class,
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_INT64 => 'Reflection\Int64T::T',
                    Type::TYPE_UINT64 => 'Reflection\Uint64T::T',
                    Type::TYPE_FIXED64 => 'Reflection\Fixed64T::T',
                    Type::TYPE_SFIXED64 => 'Reflection\SFixed64T::T',
                    Type::TYPE_SINT64 => 'Reflection\SInt64T::T',
                }),
                uses: [Number::class],
                default: Literal::new('Number', [0]),
                docType: 'Number',
            ),
            Type::TYPE_INT32,
            Type::TYPE_UINT32,
            Type::TYPE_FIXED32,
            Type::TYPE_SFIXED32,
            Type::TYPE_SINT32 => new TypeDeclaration(
                phpType: 'int',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_INT32 => 'Reflection\Int32T::T',
                    Type::TYPE_FIXED32 => 'Reflection\Fixed32T::T',
                    Type::TYPE_UINT32 => 'Reflection\Uint32T::T',
                    Type::TYPE_SFIXED32 => 'Reflection\SFixed32T::T',
                    Type::TYPE_SINT32 => 'Reflection\SInt32T::T',
                }),
                default: 0,
            ),
            Type::TYPE_BOOL => new TypeDeclaration(
                phpType: 'bool',
                reflectionType: new Literal('Reflection\BoolT::T'),
                default: false,
            ),
            Type::TYPE_STRING,
            Type::TYPE_BYTES => new TypeDeclaration(
                phpType: 'string',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_STRING => 'Reflection\StringT::T',
                    Type::TYPE_BYTES => 'Reflection\BytesT::T',
                }),
                default: '',
            ),
            default => throw new \RuntimeException("No suitable declaration found for type `{$field->type?->name}`."),
        };
    }
}
