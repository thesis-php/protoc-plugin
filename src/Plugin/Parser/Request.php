<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\Compiler\CodeGeneratorRequest;

/**
 * @api
 * @template-implements \IteratorAggregate<string, FileDescriptor>
 */
final readonly class Request implements \IteratorAggregate
{
    /**
     * @param array<string, FileDescriptor> $descriptors
     */
    public function __construct(
        public CodeGeneratorRequest $request,
        public array $descriptors,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->request->fileToGenerate as $file) {
            if (isset($this->descriptors[$file])) {
                yield $file => $this->descriptors[$file];
            }
        }
    }
}
