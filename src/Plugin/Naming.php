<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
enum Naming
{
    public static function camelCase(string $name): string
    {
        return lcfirst(self::pascalCase($name));
    }

    public static function pascalCase(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public static function namespace(string $name): string
    {
        return str_replace(' ', '\\', ucwords(str_replace('.', ' ', $name)));
    }
}
