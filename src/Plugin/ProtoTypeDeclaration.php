<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\Literal;

/**
 * @api
 */
final readonly class ProtoTypeDeclaration
{
    /**
     * @param list<string> $uses
     */
    public function __construct(
        public string $phpType,
        public Literal $reflectionType,
        public array $uses = [],
        public bool $nullable = false,
        public mixed $default = null,
        public ?string $docType = null,
        public bool $isMap = false,
    ) {}

    public function resolvedType(): string
    {
        return $this->docType ?? $this->phpType;
    }
}
