<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Plugin\Compiler;

/**
 * The plugin is executed in a child process with display_errors enabled,
 * because in that mode the CLI prints diagnostics to the same STDOUT that carries the binary response.
 */
#[CoversClass(Entrypoint::class)]
final class EntrypointErrorHandlingTest extends TestCase
{
    private const string MESSAGE = 'thesis-plugin-test-diagnostic';

    #[DataProvider('provideWarningIsReportedAsResponseErrorCases')]
    public function testWarningIsReportedAsResponseError(string $displayErrors, int $level): void
    {
        [$response, $stderr] = self::runPlugin($displayErrors, "trigger_error('" . self::MESSAGE . "', {$level});");

        self::assertNotNull($response->error, $stderr);
        self::assertStringContainsString(self::MESSAGE, $response->error);
        self::assertSame([], $response->file);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideWarningIsReportedAsResponseErrorCases(): iterable
    {
        foreach (['1', 'stdout'] as $displayErrors) {
            yield "E_USER_WARNING, display_errors={$displayErrors}" => [$displayErrors, E_USER_WARNING];
            yield "E_USER_NOTICE, display_errors={$displayErrors}" => [$displayErrors, E_USER_NOTICE];
        }
    }

    public function testDeprecationDoesNotInterruptGeneration(): void
    {
        [$response, $stderr] = self::runPlugin('1', "trigger_error('" . self::MESSAGE . "', E_USER_DEPRECATED);");

        self::assertNull($response->error, $stderr);
        self::assertNotEmpty($response->file);
        self::assertStringContainsString(self::MESSAGE, $stderr, 'Deprecation should be displayed on STDERR.');
    }

    public function testSilencedWarningIsNotConvertedToException(): void
    {
        [$response, $stderr] = self::runPlugin('1', "@trigger_error('" . self::MESSAGE . "', E_USER_WARNING);");

        self::assertNull($response->error, $stderr);
        self::assertNotEmpty($response->file);
        self::assertStringNotContainsString(self::MESSAGE, $stderr);
    }

    public function testWarningNotInErrorReportingIsNotConvertedToException(): void
    {
        [$response, $stderr] = self::runPlugin(
            '1',
            "error_reporting(E_ALL & ~E_USER_WARNING); trigger_error('" . self::MESSAGE . "', E_USER_WARNING); error_reporting(E_ALL);",
        );

        self::assertNull($response->error, $stderr);
        self::assertNotEmpty($response->file);
    }

    public function testErrorHandlerAndDisplayErrorsAreRestored(): void
    {
        $displayErrors = \ini_get('display_errors');
        $handler = static fn(): bool => false;
        set_error_handler($handler);
        $errorReporting = error_reporting(E_ALL);

        try {
            $encoder = Encoder\Builder::buildDefault();
            $output = new class implements WriteOutput {
                public string $buffer = '';

                #[\Override]
                public function write(string $buffer): void
                {
                    $this->buffer .= $buffer;
                }
            };

            new Entrypoint(new Compiler($encoder, \dirname(__DIR__) . '/map.json'), $encoder, Decoder\Builder::buildDefault())->run(
                new class implements ReadInput {
                    #[\Override]
                    public function read(): string
                    {
                        trigger_error('in-process warning', E_USER_WARNING);

                        return '';
                    }
                },
                $output,
            );

            $current = set_error_handler(null);
            restore_error_handler();
        } finally {
            error_reporting($errorReporting);
            restore_error_handler();
        }

        self::assertSame($handler, $current);
        self::assertSame($displayErrors, \ini_get('display_errors'));

        $response = Decoder\Builder::buildDefault()->decode($output->buffer, CodeGeneratorResponse::class);
        self::assertNotNull($response->error);
        self::assertStringContainsString('in-process warning', $response->error);
    }

    /**
     * @param non-empty-string $onRead PHP code executed when the plugin reads its input
     * @return array{CodeGeneratorResponse, string}
     */
    private static function runPlugin(string $displayErrors, string $onRead): array
    {
        $requestFile = self::dataDir() . '/grpc/request.hex';
        self::assertFileExists($requestFile, 'No generated testdata found. Run `make test` or `make generate-testdata` first.');

        $code = <<<PHP
            require 'vendor/autoload.php';

            \$encoder = Thesis\\Protobuf\\Encoder\\Builder::buildDefault();

            new Thesis\\Protoc\\Entrypoint(
                new Thesis\\Protoc\\Plugin\\Compiler(\$encoder, 'map.json'),
                \$encoder,
                Thesis\\Protobuf\\Decoder\\Builder::buildDefault(),
            )->run(
                new class implements Thesis\\Protoc\\ReadInput {
                    public function read(): string
                    {
                        {$onRead}

                        return hex2bin(file_get_contents(\$GLOBALS['argv'][1]));
                    }
                },
                new Thesis\\Protoc\\Io\\WriteStreamOutput(),
            );
            PHP;

        $process = proc_open(
            [PHP_BINARY, '-d', "display_errors={$displayErrors}", '-d', 'error_reporting=-1', '-r', $code, '--', $requestFile],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            \dirname(__DIR__),
        );

        self::assertIsResource($process);
        self::assertArrayHasKey(1, $pipes);
        self::assertArrayHasKey(2, $pipes);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertSame(0, proc_close($process), $stderr);

        $response = Decoder\Builder::buildDefault()->decode($stdout, CodeGeneratorResponse::class);

        // STDOUT must contain the encoded response and nothing else (no PHP diagnostics).
        self::assertSame(bin2hex(Encoder\Builder::buildDefault()->encode($response)), bin2hex($stdout), $stderr);

        return [$response, $stderr];
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
