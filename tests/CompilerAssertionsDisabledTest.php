<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protobuf\Decoder;

/**
 * zend.assertions cannot be switched to or from -1 at runtime,
 * so the plugin is executed in a child process (as with php.ini-production).
 */
#[CoversNothing]
final class CompilerAssertionsDisabledTest extends TestCase
{
    public function testServicesAreCompiledWithAssertionsDisabled(): void
    {
        $hex = file_get_contents(self::dataDir() . '/grpc/request.hex');
        self::assertIsString($hex, 'No generated testdata found. Run `make test` or `make generate-testdata` first.');

        $request = hex2bin($hex);
        self::assertIsString($request);

        $process = proc_open(
            [PHP_BINARY, '-d', 'zend.assertions=-1', '-d', 'display_errors=stderr', 'protoc-gen-php'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            \dirname(__DIR__),
        );

        self::assertIsResource($process);
        self::assertArrayHasKey(0, $pipes);
        self::assertArrayHasKey(1, $pipes);
        self::assertArrayHasKey(2, $pipes);

        fwrite($pipes[0], $request);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertSame(0, proc_close($process), $stderr);

        $response = Decoder\Builder::buildDefault()->decode($stdout, CodeGeneratorResponse::class);

        self::assertNull($response->error, $stderr);
        self::assertNotEmpty($response->file);
    }

    private static function dataDir(): string
    {
        $root = getenv('TESTDATA_DIR');
        if (\is_string($root) && $root !== '') {
            return $root;
        }

        return \dirname(__DIR__) . '/var/testdata';
    }
}
