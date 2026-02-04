<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Type;

use Nette\PhpGenerator\PhpNamespace;
use Thesis\Protoc\Plugin\FieldDescriptor;
use Thesis\Protoc\Plugin\GrpcTypeDeclaration;
use Thesis\Protoc\Plugin\GrpcTypeDeclarationResolver;
use Thesis\Protoc\Plugin\ProtoTypeDeclaration;
use Thesis\Protoc\Plugin\ProtoTypeDeclarationResolver;

/**
 * @api
 */
final readonly class AggregateTypeDeclarationResolver implements
    ProtoTypeDeclarationResolver,
    GrpcTypeDeclarationResolver
{
    /**
     * @param list<ProtoTypeDeclarationResolver> $protoResolvers
     * @param list<GrpcTypeDeclarationResolver> $grpcResolvers
     */
    public function __construct(
        private array $protoResolvers = [],
        private array $grpcResolvers = [],
    ) {}

    #[\Override]
    public function resolveProtoType(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): ProtoTypeDeclaration {
        foreach ($this->protoResolvers as $resolver) {
            $type = $resolver->resolveProtoType($field, $namespace);
            if ($type !== null) {
                return $type;
            }
        }

        throw new \RuntimeException("No suitable resolver found for field: `{$field->name}`.");
    }

    #[\Override]
    public function resolveGrpcType(string $fqcn): GrpcTypeDeclaration
    {
        foreach ($this->grpcResolvers as $resolver) {
            $type = $resolver->resolveGrpcType($fqcn);
            if ($type !== null) {
                return $type;
            }
        }

        throw new \RuntimeException("No suitable resolver found for type: `{$fqcn}`.");
    }
}
