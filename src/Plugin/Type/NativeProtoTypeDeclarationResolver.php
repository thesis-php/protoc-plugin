<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Type;

use BcMath\Number;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protobuf\Compiler\FieldDescriptorProto\Type;
use Thesis\Protoc\Plugin\FieldDescriptor;
use Thesis\Protoc\Plugin\ProtoTypeDeclaration;
use Thesis\Protoc\Plugin\ProtoTypeDeclarationResolver;

/**
 * @api
 */
final readonly class NativeProtoTypeDeclarationResolver implements ProtoTypeDeclarationResolver
{
    #[\Override]
    public function resolveProtoType(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ?ProtoTypeDeclaration {
        if ($field->typeName !== null) {
            return null;
        }

        if (\in_array($field->type, [Type::TYPE_ENUM, Type::TYPE_MESSAGE, Type::TYPE_GROUP], true)) {
            return null;
        }

        return match ($type = $field->type) {
            Type::TYPE_FLOAT,
            Type::TYPE_DOUBLE => new ProtoTypeDeclaration(
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
            Type::TYPE_SINT64 => new ProtoTypeDeclaration(
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
            Type::TYPE_SINT32 => new ProtoTypeDeclaration(
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
            Type::TYPE_BOOL => new ProtoTypeDeclaration(
                phpType: 'bool',
                reflectionType: new Literal('Reflection\BoolT::T'),
                default: false,
            ),
            Type::TYPE_STRING,
            Type::TYPE_BYTES => new ProtoTypeDeclaration(
                phpType: 'string',
                reflectionType: new Literal(match ($type) {
                    Type::TYPE_STRING => 'Reflection\StringT::T',
                    Type::TYPE_BYTES => 'Reflection\BytesT::T',
                }),
                default: '',
            ),
            default => throw new \RuntimeException('No suitable declaration found.'),
        };
    }
}
