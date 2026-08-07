<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Thesis\Protoc\Plugin\NameIndex;

/**
 * One .proto file's contribution to its package descriptor registry: a private
 * buffer constant plus the messages/enums/services it registers.
 *
 * @api
 */
final readonly class DescriptorFile
{
    /**
     * @param non-empty-string $constant name of the private buffer constant, e.g. CODE_DESCRIPTOR_BUFFER
     * @param list<string> $dependencies
     */
    public function __construct(
        public string $constant,
        public string $name,
        public array $dependencies,
        public NameIndex $index,
        public string $buffer,
    ) {}
}
