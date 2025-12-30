<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

/**
 * @api
 */
final class Printer extends PsrPrinter
{
    private const string METHOD_BODY_SUFFIX = "{\n}\n";

    #[\Override]
    public function printMethod(Method $method, ?PhpNamespace $namespace = null, bool $isInterface = false): string
    {
        $method = parent::printMethod($method, $namespace, $isInterface);

        return str_ends_with($method, self::METHOD_BODY_SUFFIX) ? rtrim($method, self::METHOD_BODY_SUFFIX) . "{}\n" : $method;
    }
}
