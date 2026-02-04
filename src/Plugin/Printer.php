<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\TraitType;

/**
 * @api
 */
final class Printer extends PsrPrinter
{
    private const string EMPTY_BODY_SUFFIX = "{\n}\n";

    #[\Override]
    public function printMethod(Method $method, ?PhpNamespace $namespace = null, bool $isInterface = false): string
    {
        $method = parent::printMethod($method, $namespace, $isInterface);

        return str_ends_with($method, self::EMPTY_BODY_SUFFIX) ? rtrim($method, self::EMPTY_BODY_SUFFIX) . "{}\n" : $method;
    }

    #[\Override]
    public function printClass(TraitType|InterfaceType|ClassType|EnumType $class, ?PhpNamespace $namespace = null): string
    {
        $class = parent::printClass($class, $namespace);

        return str_ends_with($class, self::EMPTY_BODY_SUFFIX) ? rtrim($class, self::EMPTY_BODY_SUFFIX) . " {}\n" : $class;
    }
}
