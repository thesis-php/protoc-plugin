<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\Literal;

/**
 * @api
 */
final readonly class TypeDeclaration
{
    /**
     * @param list<non-empty-string> $uses
     */
    public function __construct(
        public string $phpType,
        public Literal $reflectionType,
        public bool $nullable = false,
        public bool $isMap = false,
        public ?string $docType = null,
        public mixed $default = null,
        public array $uses = [],
    ) {}

    public function resolvedType(): string
    {
        return $this->docType ?? $this->phpType;
    }
}
