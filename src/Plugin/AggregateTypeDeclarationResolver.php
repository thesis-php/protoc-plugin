<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\PhpNamespace;

/**
 * @api
 */
final readonly class AggregateTypeDeclarationResolver
{
    /**
     * @param list<TypeDeclarationResolver> $resolvers
     */
    public function __construct(
        private array $resolvers,
    ) {}

    public function resolve(
        FieldDescriptor $field,
        PhpNamespace $namespace,
    ): TypeDeclaration {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($field)) {
                return $resolver->resolve($field, $namespace);
            }
        }

        throw new \RuntimeException("No suitable resolver found for field: `{$field->name}`.");
    }
}
