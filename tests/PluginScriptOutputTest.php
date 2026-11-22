<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * Runs the real protoc-gen-php script with display_errors pointing to STDOUT,
 * where the CLI would otherwise print diagnostics next to the binary response.
 */
#[CoversNothing]
final class PluginScriptOutputTest extends TestCase
{
    private const string MESSAGE = 'thesis-plugin-pre-run-diagnostic';

    #[TestWith(['1'])]
    #[TestWith(['stdout'])]
    public function testDiagnosticsBeforeRunDoNotCorruptStdout(string $displayErrors): void
    {
        $hex = file_get_contents(self::dataDir() . '/grpc/request.hex');
        self::assertIsString($hex, 'No generated testdata found. Run `make test` or `make generate-testdata` first.');

        $request = hex2bin($hex);
        self::assertIsString($request);

        // The script loads the autoloader from $_composer_autoload_path (as Composer's bin proxy does),
        // which lets us emit a diagnostic deterministically before Entrypoint::run().
        $autoload = tempnam(sys_get_temp_dir(), 'thesis-plugin-autoload');
        self::assertIsString($autoload);

        try {
            file_put_contents($autoload, \sprintf(
                "<?php\ntrigger_error(%s, E_USER_DEPRECATED);\nreturn require %s;\n",
                var_export(self::MESSAGE, true),
                var_export(\dirname(__DIR__) . '/vendor/autoload.php', true),
            ));

            $code = <<<'PHP'
                $_composer_autoload_path = $argv[1];
                require 'protoc-gen-php';
                fwrite(STDERR, "\nDISPLAY_ERRORS=" . ini_get('display_errors') . "\n");
                PHP;

            $process = proc_open(
                [PHP_BINARY, '-d', "display_errors={$displayErrors}", '-d', 'error_reporting=-1', '-r', $code, '--', $autoload],
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
        } finally {
            @unlink($autoload);
        }

        self::assertStringContainsString("\nDISPLAY_ERRORS=stderr\n", $stderr);
        self::assertStringContainsString(self::MESSAGE, $stderr, 'The diagnostic should be displayed on STDERR.');

        $response = Decoder\Builder::buildDefault()->decode($stdout, CodeGeneratorResponse::class);

        // STDOUT must contain the encoded response and nothing else (no PHP diagnostics).
        self::assertSame(bin2hex(Encoder\Builder::buildDefault()->encode($response)), bin2hex($stdout), $stderr);
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
