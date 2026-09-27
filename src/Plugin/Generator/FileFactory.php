<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Thesis\Protoc\Plugin\Naming;
use Thesis\Protoc\Plugin\Printer;

/**
 * @api
 */
final readonly class FileFactory
{
    private PsrPrinter $printer;

    /**
     * @param string $path directory the $namespace maps onto
     * @param ?string $namespace namespace the file the code belongs to is generated into
     */
    public function __construct(
        private string $generatedDoc,
        private string $path,
        private ?string $namespace = null,
    ) {
        $this->printer = new Printer()->setTypeResolving(false);
    }

    public function create(
        PhpNamespace|string $code,
        string $path,
    ): CodeGeneratorResponse\File {
        $content = match (true) {
            $code instanceof PhpNamespace => $this->printer->printFile(
                new PhpFile()
                    ->setStrictTypes()
                    ->setComment($this->generatedDoc)
                    ->add($code),
            ),
            default => \sprintf(
                <<<'PHP'
<?php

%s

declare(strict_types=1);

%s
PHP,
                $this->generatedDoc,
                $code,
            ),
        };

        return new CodeGeneratorResponse\File(
            name: $code instanceof PhpNamespace
                ? $this->resolvePath($code->getName(), $path)
                : \sprintf('%s/%s.php', $this->path, $path),
            content: $content,
        );
    }

    /**
     * Types normally live under the namespace of their file, so the path they are written
     * to is the one configured for that file. A type moved elsewhere by a relocation rule
     * no longer belongs there and is written under its own namespace instead.
     */
    private function resolvePath(string $namespace, string $path): string
    {
        if ($this->namespace === null || $namespace === $this->namespace || str_starts_with($namespace, "{$this->namespace}\\")) {
            return \sprintf('%s/%s.php', $this->path, Naming::path($path));
        }

        $paths = explode('.', $path);

        return \sprintf(
            '%s/%s.php',
            str_replace('\\', '/', $namespace),
            Naming::pascalCase($paths[\count($paths) - 1]),
        );
    }
}
