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

    public function __construct(
        private string $generatedDoc,
        private string $path,
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
            name: \sprintf('%s/%s.php', $this->path, $code instanceof  PhpNamespace ? Naming::path($path) : $path),
            content: $content,
        );
    }
}
