<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protoc\Plugin\Compiler;

#[CoversClass(Compiler::class)]
final class CompilerTest extends TestCase
{
    #[DataProvider('provideCompileSnapshotsCases')]
    public function testCompileSnapshots(string $file): void
    {
        $hex = file_get_contents(__DIR__ . "/testdata/{$file}");
        self::assertIsString($hex);

        $bytes = hex2bin($hex);
        self::assertIsString($bytes);

        $decoder = Decoder\Builder::buildDefault();
        $encoder = Encoder\Builder::buildDefault();

        $request = $decoder->decode($bytes, CodeGeneratorRequest::class);

        $actual = self::collectFiles(new Compiler($encoder)->compile($request));
        $expected = self::collectSnapshots(__DIR__ . '/snapshots/' . substr($file, 0, -4));

        foreach ($actual as $name => $content) {
            self::assertArrayHasKey($name, $expected);
            self::assertSame(
                self::normalize($expected[$name]),
                self::normalize($content),
                "Generated file content mismatch: {$file} -> {$name}",
            );
        }
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideCompileSnapshotsCases(): iterable
    {
        $fixtures = glob(__DIR__ . '/testdata/*/*.txt');
        self::assertIsArray($fixtures);

        sort($fixtures);

        foreach ($fixtures as $fixture) {
            yield [
                substr($fixture, \strlen(__DIR__ . '/testdata/')),
            ];
        }
    }

    /**
     * @param list<File> $files
     * @return array<string, string>
     */
    private static function collectFiles(array $files): array
    {
        $collection = [];

        foreach ($files as $file) {
            self::assertNotNull($file->name);
            self::assertNotNull($file->content);

            $collection[$file->name] = $file->content;
        }

        ksort($collection);

        return $collection;
    }

    /**
     * @return array<string, string>
     */
    private static function collectSnapshots(string $snapshotDir): array
    {
        self::assertDirectoryExists($snapshotDir);

        $collection = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $snapshotDir,
                flags: \FilesystemIterator::SKIP_DOTS,
            ),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            self::assertIsString($content);

            $name = str_replace('\\', '/', substr($file->getPathname(), \strlen($snapshotDir) + 1));
            $collection[$name] = $content;
        }

        ksort($collection);

        return $collection;
    }

    private static function normalize(string $content): string
    {
        $content = str_replace("\r\n", "\n", $content);

        return (string) preg_replace(
            '/thesis\/protoc-plugin — v[^\n]+/',
            'thesis/protoc-plugin — v<version>',
            $content,
        );
    }
}
