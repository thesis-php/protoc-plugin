<?php

declare(strict_types=1);

namespace Thesis\Protoc\GeneratedCode;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Checks the committed snapshots as PHP code, since CompilerTest only compares them as text:
 *  1. every file compiles (php -l);
 *  2. every class, interface and enum loads, enums give their cases, messages without required constructor
 *     parameters are instantiated, autoload.metadata.php registers every DescriptorRegistry in the pool;
 *  3. messages survive an encode/decode round-trip through thesis/protobuf: the instantiated ones and the
 *     populated samples from samples/<case>.php.
 *
 * Each case runs in its own PHP process (see SnapshotValidator): cases declare classes with the same names,
 * and a broken snapshot may end the process with a fatal error.
 */
#[CoversNothing]
final class GeneratedCodeTest extends TestCase
{
    private const string SNAPSHOTS_DIR = __DIR__ . '/../snapshots';
    private const string SAMPLES_DIR = __DIR__ . '/samples';

    /**
     * The gRPC code ({Service}Client, {Service}Server, {Service}ServerRegistry) depends on thesis/grpc and amphp,
     * which are not dev dependencies of the plugin: thesis/grpc pulls in the whole amphp HTTP stack and its
     * thesis/googleapis-rpc-types registers google.rpc.* in the pool on autoload, which clashes with the
     * namespace_mapping case generating the same types. Such files are only compiled (level 1), never loaded.
     */
    private const string GRPC_RUNTIME_IMPORT = '/^use (Thesis\\\Grpc|Amp)\b/m';

    /**
     * Known defects of the committed snapshots, one entry per failing step of SnapshotValidator:
     * case => [step => what is wrong]. A listed step must still fail, so the entry has to be removed with the fix.
     */
    private const array KNOWN_DEFECTS = [
        // Presence of required fields and of fields with a default is lost on the wire. They are generated as
        // non-nullable properties, and the runtime writes a non-nullable field only when it is not the zero value:
        // a zero required field is not written at all (the decoder then rejects the message), and the zero value
        // of a field with a non-zero default comes back as that default.
        'proto2' => [
            "round-trip sample 'required fields set to zero values'" => 'zero required fields are not written',
            "round-trip sample 'required enum with a default set to the zero case'" => 'decoded as the default case',
            "round-trip sample 'optional enum with a default set to the zero case'" => 'decoded as the default case',
        ],
        'editions' => [
            "round-trip sample 'legacy required field set to zero value'" => 'zero required field is not written',
            "round-trip sample 'legacy required field with a default set to zero value'" => 'decoded as the default',
        ],
    ];

    /**
     * thesis/protobuf before 0.2.2 writes expanded repeated fields (ListT(..., false)) packed and then cannot read
     * them back, so the populated samples do not round-trip on the lowest dependencies. Remove the gate once the
     * plugin requires thesis/protobuf ^0.2.2.
     */
    private const string SAMPLES_MIN_RUNTIME = '0.2.2';

    #[DataProvider('provideSnapshotCases')]
    public function testSnapshotFilesCompile(string $case): void
    {
        $files = self::phpFiles($case);

        [$exitCode, $output] = self::runPhp(
            ['-d', 'display_errors=stdout', '-d', 'error_reporting=-1', '-l', ...$files],
            self::SNAPSHOTS_DIR . "/{$case}",
        );

        $failures = [];
        $checked = [];
        $diagnostics = '';

        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^(No syntax errors detected in|Errors parsing) (.+)$/', $line, $matches) === 1) {
                $checked[] = $matches[2];

                // Anything printed before the verdict, deprecations included, is a failure.
                if ($matches[1] === 'Errors parsing' || trim($diagnostics) !== '') {
                    $failures[] = "{$matches[2]}:\n" . trim($diagnostics);
                }

                $diagnostics = '';
            } else {
                $diagnostics .= $line . "\n";
            }
        }

        self::assertTrue($failures === [], "Snapshot case {$case}: files do not compile:\n" . implode("\n\n", $failures));
        self::assertSame($files, $checked, "Snapshot case {$case}: unexpected php -l output:\n{$output}");
        self::assertSame(0, $exitCode, "Snapshot case {$case}: php -l failed:\n{$output}");
    }

    #[DataProvider('provideSnapshotCases')]
    public function testSnapshotCodeLoadsAndRoundTrips(string $case): void
    {
        $lintOnly = array_values(array_filter(
            self::phpFiles($case),
            static fn(string $file): bool => preg_match(self::GRPC_RUNTIME_IMPORT, self::read(self::SNAPSHOTS_DIR . "/{$case}/{$file}")) === 1,
        ));

        $samples = self::SAMPLES_DIR . "/{$case}.php";
        $runtime = InstalledVersions::getVersion('thesis/protobuf') ?? '0';
        $skipSamples = is_file($samples) && version_compare($runtime, self::SAMPLES_MIN_RUNTIME, '<');

        $code = <<<'PHP'
            require 'vendor/autoload.php';
            exit(\Thesis\Protoc\GeneratedCode\SnapshotValidator::main(array_slice($argv, 1)));
            PHP;

        [$exitCode, $output, $stderr] = self::runPhp(
            ['-r', $code, '--', self::SNAPSHOTS_DIR . "/{$case}", is_file($samples) && !$skipSamples ? $samples : '', ...$lintOnly],
            \dirname(__DIR__, 2),
        );

        $knownDefects = $skipSamples
            ? array_filter(self::KNOWN_DEFECTS[$case] ?? [], static fn(string $step): bool => !str_starts_with($step, 'round-trip sample '), ARRAY_FILTER_USE_KEY)
            : self::KNOWN_DEFECTS[$case] ?? [];
        $step = null;
        $failures = [];
        $noise = '';

        foreach (explode("\n", trim($output)) as $line) {
            $event = json_decode($line, true);

            if (\is_array($event) && \is_string($event['step'] ?? null)) {
                $step = $event['step'];
            } elseif (\is_array($event) && \is_string($event['fail'] ?? null) && \is_string($event['error'] ?? null)) {
                if (isset($knownDefects[$event['fail']])) {
                    unset($knownDefects[$event['fail']]);
                } else {
                    $failures[] = "- {$event['fail']}:\n  {$event['error']}";
                }
            } else {
                $noise .= $line . "\n";
            }
        }

        self::assertContains(
            $exitCode,
            [0, 1],
            "Snapshot case {$case}: the PHP process died (exit code {$exitCode}) at step \"{$step}\":\n" . trim($stderr . "\n" . $noise),
        );
        self::assertSame('', trim($stderr . $noise), "Snapshot case {$case}: unexpected output of the PHP process.");
        self::assertTrue($failures === [], "Snapshot case {$case}:\n" . implode("\n", $failures));
        self::assertSame(
            [],
            $knownDefects,
            "Snapshot case {$case}: these known defects do not reproduce any more, remove them from KNOWN_DEFECTS.",
        );

        if ($skipSamples) {
            self::markTestSkipped("Samples of {$case} are not round-tripped: thesis/protobuf {$runtime} < " . self::SAMPLES_MIN_RUNTIME . '.');
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSnapshotCases(): iterable
    {
        foreach (self::cases() as $case) {
            yield $case => [$case];
        }
    }

    public function testEverySamplesFileBelongsToSnapshotCase(): void
    {
        $samples = glob(self::SAMPLES_DIR . '/*.php');
        self::assertIsArray($samples);

        $orphans = array_diff(array_map(static fn(string $file): string => basename($file, '.php'), $samples), self::cases());

        self::assertSame([], array_values($orphans), 'Samples without a snapshot case.');
        self::assertSame([], array_diff(array_keys(self::KNOWN_DEFECTS), self::cases()), 'Known defects of missing snapshot cases.');
    }

    /**
     * @return list<string>
     */
    private static function cases(): array
    {
        $dirs = glob(self::SNAPSHOTS_DIR . '/*', GLOB_ONLYDIR);
        self::assertIsArray($dirs);
        self::assertNotSame([], $dirs, 'No snapshot cases found in tests/snapshots.');

        $cases = array_map(basename(...), $dirs);
        sort($cases);

        return $cases;
    }

    /**
     * @return list<string> paths relative to the case directory, sorted
     */
    private static function phpFiles(string $case): array
    {
        $root = self::SNAPSHOTS_DIR . "/{$case}";
        $files = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = substr($file->getPathname(), \strlen($root) + 1);
            }
        }

        sort($files);
        self::assertNotSame([], $files, "Snapshot case {$case} has no PHP files.");

        return $files;
    }

    private static function read(string $file): string
    {
        $content = file_get_contents($file);
        self::assertIsString($content);

        return $content;
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string} exit code, stdout, stderr
     */
    private static function runPhp(array $arguments, string $cwd): array
    {
        $stderr = tmpfile();
        self::assertIsResource($stderr);

        $process = proc_open(
            [PHP_BINARY, ...$arguments],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['pipe', 'w'],
                2 => $stderr,
            ],
            $pipes,
            $cwd,
        );

        self::assertIsResource($process);
        self::assertArrayHasKey(1, $pipes);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        self::assertIsString($output);

        $exitCode = proc_close($process);

        rewind($stderr);
        $errors = stream_get_contents($stderr);
        fclose($stderr);
        self::assertIsString($errors);

        return [$exitCode, $output, $errors];
    }
}
