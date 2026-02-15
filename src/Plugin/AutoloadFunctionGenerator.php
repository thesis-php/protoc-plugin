<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protoc\Plugin\Generator\FileFactory;

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
\Thesis\Protobuf\Pool\Registry::get()->register(
%s,
);

PHP,
                implode(",\n", array_map(
                    static fn(string $descriptor) => "    new \\Thesis\\Protobuf\\Pool\\OnceRegistrar(new \\{$descriptor}())",
                    $descriptors,
                )),
            ),
            path: 'autoload.metadata',
        );
    }
}
