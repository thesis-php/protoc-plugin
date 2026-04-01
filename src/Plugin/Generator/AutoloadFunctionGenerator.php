<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Google\Protobuf\Compiler\CodeGeneratorResponse;

/**
 * @api
 */
final readonly class AutoloadFunctionGenerator
{
    public function __construct(
        private FileFactory $files,
    ) {}

    /**
     * @param list<string> $descriptors
     */
    public function generateAutoloadFile(array $descriptors): CodeGeneratorResponse\File
    {
        return $this->files->create(
            code: \sprintf(
                <<<'PHP'
\Thesis\Protobuf\Registry\Pool::get()->register(
%s,
);

PHP,
                implode(",\n", array_map(
                    static fn(string $descriptor) => "    new \\Thesis\\Protobuf\\Registry\\OnceRegistrar(new \\{$descriptor}())",
                    $descriptors,
                )),
            ),
            path: 'autoload.metadata',
        );
    }
}
