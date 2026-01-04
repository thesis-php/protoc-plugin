<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
enum Naming
{
    /** @var array<string, 1> */
    private const array CLASS_NAMES_RESERVED_WORDS = [
        'bool' => 1,
        'false' => 1,
        'float' => 1,
        'int' => 1,
        'iterable' => 1,
        'mixed' => 1,
        'never' => 1,
        'null' => 1,
        'object' => 1,
        'parent' => 1,
        'self' => 1,
        'string' => 1,
        'true' => 1,
        'void' => 1,
        '__halt_compiler' => 1,
        'abstract' => 1,
        'and' => 1,
        'array' => 1,
        'as' => 1,
        'break' => 1,
        'callable' => 1,
        'case' => 1,
        'catch' => 1,
        'class' => 1,
        'clone' => 1,
        'const' => 1,
        'continue' => 1,
        'declare' => 1,
        'default' => 1,
        'die' => 1,
        'do' => 1,
        'echo' => 1,
        'else' => 1,
        'elseif' => 1,
        'empty' => 1,
        'enddeclare' => 1,
        'endfor' => 1,
        'endforeach' => 1,
        'endif' => 1,
        'endswitch' => 1,
        'endwhile' => 1,
        'eval' => 1,
        'exit' => 1,
        'extends' => 1,
        'final' => 1,
        'finally' => 1,
        'fn' => 1,
        'for' => 1,
        'foreach' => 1,
        'function' => 1,
        'global' => 1,
        'goto' => 1,
        'if' => 1,
        'implements' => 1,
        'include' => 1,
        'include_once' => 1,
        'instanceof' => 1,
        'insteadof' => 1,
        'interface' => 1,
        'isset' => 1,
        'list' => 1,
        'match' => 1,
        'namespace' => 1,
        'new' => 1,
        'or' => 1,
        'print' => 1,
        'private' => 1,
        'protected' => 1,
        'public' => 1,
        'readonly' => 1,
        'require' => 1,
        'require_once' => 1,
        'return' => 1,
        'static' => 1,
        'switch' => 1,
        'throw' => 1,
        'trait' => 1,
        'try' => 1,
        'unset' => 1,
        'use' => 1,
        'var' => 1,
        'while' => 1,
        'xor' => 1,
        'yield' => 1,
    ];

    /** @var array<string, 1> */
    private const array ENUM_CASE_RESERVED_WORDS = [
        'class' => 1,
    ];

    public static function camelCase(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
    }

    public static function pascalCase(string $name): string
    {
        return self::secure(ucfirst(self::camelCase($name)));
    }

    public static function namespace(string $name): string
    {
        $name = implode('.', array_map(
            self::pascalCase(...),
            explode('.', $name),
        ));

        return self::secure(str_replace(' ', '\\', ucwords(str_replace('.', ' ', $name))));
    }

    /**
     * @param list<string> $paths
     */
    public static function joinNamespace(array $paths): string
    {
        return implode(
            '\\',
            array_map(
                self::pascalCase(...),
                array_merge(
                    ...array_map(
                        static fn(string $path) => explode('.', $path),
                        $paths,
                    ),
                ),
            ),
        );
    }

    public static function path(string $path): string
    {
        return implode('/', array_map(
            self::pascalCase(...),
            explode('.', $path),
        ));
    }

    public static function extract(string $namespace, int $levels): string
    {
        return implode('\\', \array_slice(explode('\\', $namespace), $levels));
    }

    public static function secure(string $name): string
    {
        return isset(self::CLASS_NAMES_RESERVED_WORDS[strtolower($name)]) ? "{$name}_" : $name;
    }

    public static function secureEnumCase(string $name): string
    {
        return isset(self::ENUM_CASE_RESERVED_WORDS[strtolower($name)]) ? "{$name}_" : $name;
    }
}
