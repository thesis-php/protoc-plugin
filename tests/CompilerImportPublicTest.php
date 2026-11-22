<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Plugin\Compiler;
use Thesis\Protoc\Plugin\Dependency\Registry;

/**
 * The import_public snapshot case is generated with all its files in file_to_generate. The usual run, though,
 * generates only the importer, and the files it reaches through "import public" come only as dependencies
 * (proto_file). The request protoc sent for the case is narrowed to one file here: the code generated for it must
 * be the same as in the snapshot.
 */
#[CoversClass(Compiler::class)]
#[CoversClass(Registry::class)]
final class CompilerImportPublicTest extends TestCase
{
    private const string CASE = 'import_public';

    /**
     * @param non-empty-string $file
     * @param list<string> $expected generated files, autoload.metadata.php aside (it is shared by the whole case)
     */
    #[DataProvider('provideOnlyImporterIsGeneratedCases')]
    public function testOnlyImporterIsGenerated(string $file, array $expected): void
    {
        $request = self::request();

        self::assertContains($file, $request->fileToGenerate);

        $request = new CodeGeneratorRequest(
            fileToGenerate: [$file],
            parameter: $request->parameter,
            protoFile: $request->protoFile,
            compilerVersion: $request->compilerVersion,
        );

        $generated = [];

        foreach (new Compiler(Encoder\Builder::buildDefault(), __DIR__ . '/../map.json')->compile($request) as $out) {
            self::assertNotNull($out->name);
            self::assertNotNull($out->content);

            if (basename($out->name) !== 'autoload.metadata.php') {
                $generated[$out->name] = $out->content;
            }
        }

        ksort($generated);

        self::assertSame($expected, array_keys($generated));

        foreach ($generated as $name => $content) {
            $snapshot = file_get_contents(__DIR__ . '/snapshots/' . self::CASE . "/{$name}");
            self::assertIsString($snapshot);
            self::assertSame(self::normalize($snapshot), self::normalize($content), $name);
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, list<string>}>
     */
    public static function provideOnlyImporterIsGeneratedCases(): iterable
    {
        yield 'messages, maps and a service with re-exported types' => ['a.proto', [
            'T/Pub/A/A.php',
            'T/Pub/A/DescriptorRegistry.php',
            'T/Pub/A/LookupClient.php',
            'T/Pub/A/LookupServer.php',
            'T/Pub/A/LookupServerRegistry.php',
        ]];

        yield 'enum defaults with a re-exported enum' => ['legacy.proto', [
            'T/Pub/Legacy/DescriptorRegistry.php',
            'T/Pub/Legacy/Legacy.php',
        ]];

        yield 'file using the types it re-exports' => ['reexport.proto', [
            'T/Pub/Re/DescriptorRegistry.php',
            'T/Pub/Re/Wrapper.php',
        ]];
    }

    private static function request(): CodeGeneratorRequest
    {
        $root = getenv('TESTDATA_DIR');
        $root = \is_string($root) && $root !== '' ? $root : \dirname(__DIR__) . '/var/testdata';

        $hex = file_get_contents($root . '/' . self::CASE . '/request.hex');
        self::assertIsString($hex, 'No generated testdata found. Run `make test` or `make generate-testdata` first.');

        $bytes = hex2bin($hex);
        self::assertIsString($bytes);

        return Decoder\Builder::buildDefault()->decode($bytes, CodeGeneratorRequest::class);
    }

    private static function normalize(string $content): string
    {
        return (string) preg_replace('/thesis\/protoc-plugin — v[^\n]+/', 'thesis/protoc-plugin — v<version>', $content);
    }
}
