<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class CompilerMemoryLimitTest extends TestCase
{
    public function testCompilerUsesMemoryLimitFromEnvironment(): void
    {
        $memoryLimit = self::runCompilerScript(
            initialMemoryLimit: '64M',
            envMemoryLimit: '1G',
        );

        self::assertSame('1G', $memoryLimit);
    }

    public function testCompilerRaisesDefaultMemoryLimitTo512M(): void
    {
        $memoryLimit = self::runCompilerScript(
            initialMemoryLimit: '64M',
        );

        self::assertSame('512M', $memoryLimit);
    }

    private static function runCompilerScript(string $initialMemoryLimit, ?string $envMemoryLimit = null): string
    {
        $code = <<<'PHP'
require 'bin/compiler.php';
fwrite(STDERR, "\nMEMORY_LIMIT=" . ini_get('memory_limit') . "\n");
PHP;

        $process = proc_open(
            [PHP_BINARY, '-d', "memory_limit={$initialMemoryLimit}", '-r', $code],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            \dirname(__DIR__),
            $envMemoryLimit !== null
                ? ['THESIS_PLUGIN_MEMORY_LIMIT' => $envMemoryLimit]
                : [],
        );

        self::assertIsResource($process);
        self::assertArrayHasKey(2, $pipes);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertIsString($stderr);

        self::assertSame(0, proc_close($process), $stderr);

        self::assertMatchesRegularExpression('/MEMORY_LIMIT=(.+)\n/', $stderr);
        preg_match('/MEMORY_LIMIT=(.+)\n/', $stderr, $matches);

        return trim($matches[1] ?? '');
    }
}
