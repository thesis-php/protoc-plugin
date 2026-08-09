<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Mapping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protoc\Exception\InvalidNamespaceMapping;

#[CoversClass(TypeMap::class)]
#[CoversClass(ExactRule::class)]
#[CoversClass(PatternRule::class)]
final class TypeMapTest extends TestCase
{
    public function testEmptyMapRewritesNothing(): void
    {
        $map = new TypeMap();

        self::assertNull($map->rewriteType('google.protobuf.Any'));
        self::assertNull($map->rewritePackage('google.protobuf'));
    }

    public function testShippedMapIsValid(): void
    {
        $map = TypeMap::fromFile(__DIR__ . '/../../../map.json');

        self::assertSame('thesis.google.protobuf.Any', $map->rewriteType('google.protobuf.Any'));
        self::assertSame('thesis.google.protobuf', $map->rewritePackage('google.protobuf'));
    }

    #[DataProvider('provideRewriteTypeCases')]
    public function testRewriteType(string $type, ?string $expected): void
    {
        self::assertSame($expected, self::map()->rewriteType($type));
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideRewriteTypeCases(): iterable
    {
        yield 'pattern' => ['google.protobuf.Any', 'thesis.google.protobuf.Any'];
        yield 'pattern with a leading dot' => ['.google.protobuf.Any', 'thesis.google.protobuf.Any'];
        yield 'nested type' => ['google.protobuf.Struct.FieldsEntry', 'thesis.google.protobuf.Struct.FieldsEntry'];
        yield 'nested package' => ['google.protobuf.compiler.Version', 'thesis.google.protobuf.compiler.Version'];
        yield 'exact rule wins over a pattern' => ['google.rpc.Status', 'thesis.errors.Status'];
        yield 'the longest pattern wins' => ['google.rpc.context.Rule', 'thesis.context.Rule'];
        yield 'exact rule' => ['demo.Type', 'thesis.demo.Type'];
        yield 'sibling of an exact rule' => ['demo.Another', null];
        yield 'unrelated package' => ['thesis.queue.v1.PushRequest', null];
        yield 'partially matching package' => ['googleapis.protobuf.Any', null];
    }

    #[DataProvider('provideRewritePackageCases')]
    public function testRewritePackage(string $package, ?string $expected): void
    {
        self::assertSame($expected, self::map()->rewritePackage($package));
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideRewritePackageCases(): iterable
    {
        yield 'exactly the pattern' => ['google.protobuf', 'thesis.google.protobuf'];
        yield 'nested package' => ['google.protobuf.compiler', 'thesis.google.protobuf.compiler'];
        yield 'the longest pattern wins' => ['google.rpc.context', 'thesis.context'];
        yield 'an exact rule says nothing about the package' => ['demo', null];
        yield 'unrelated package' => ['thesis.queue.v1', null];
    }

    #[DataProvider('provideInvalidMappingCases')]
    public function testInvalidMapping(string $json, string $message): void
    {
        $this->expectException(InvalidNamespaceMapping::class);
        $this->expectExceptionMessageMatches($message);

        TypeMap::fromJson($json, 'test.json');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidMappingCases(): iterable
    {
        yield 'not a json' => ['{', '/is not a valid json/'];
        yield 'not an object' => ['[]', '/must be a json object/'];
        yield 'rules are not a list' => ['{"rules": {"a": "b"}}', '/must contain a list of rules/'];
        yield 'rule is not an object' => ['{"rules": ["google.protobuf.*"]}', '/must be a json object/'];
        yield 'unknown key' => ['{"rules": [{"from": "a.B", "to": "b.B", "when": "always"}]}', '/unknown keys: when/'];
        yield 'missing to' => ['{"rules": [{"from": "a.B"}]}', "/'to' key/"];
        yield 'empty from' => ['{"rules": [{"from": "", "to": "b.B"}]}', "/'from' key/"];
        yield 'pattern mixed with an exact name' => ['{"rules": [{"from": "a.*", "to": "b.B"}]}', '/mixes a pattern with an exact name/'];
        yield 'renaming a type' => ['{"rules": [{"from": "a.B", "to": "b.C"}]}', '/renames a type/'];
        yield 'invalid name' => ['{"rules": [{"from": "a..B", "to": "b..B"}]}', '/is not a valid protobuf name/'];
        yield 'invalid pattern' => ['{"rules": [{"from": "*", "to": "b.*"}]}', '/mixes a pattern with an exact name/'];
        yield 'duplicated rule' => [
            '{"rules": [{"from": "a.*", "to": "b.*"}, {"from": "a.*", "to": "c.*"}]}',
            "/declares 'a.\\*' more than once/",
        ];
    }

    public function testDescriptionIsAllowed(): void
    {
        $map = TypeMap::fromJson('{"rules": [{"from": "a.*", "to": "b.*"}]}');

        self::assertSame('b.C', $map->rewriteType('a.C'));
    }

    private static function map(): TypeMap
    {
        return TypeMap::fromJson(
            <<<'JSON'
            {
              "rules": [
                {"from": "google.protobuf.*", "to": "thesis.google.protobuf.*"},
                {"from": "google.rpc.*", "to": "thesis.rpc.*"},
                {"from": "google.rpc.context.*", "to": "thesis.context.*"},
                {"from": "google.rpc.Status", "to": "thesis.errors.Status"},
                {"from": "demo.Type", "to": "thesis.demo.Type"}
              ]
            }
            JSON,
        );
    }
}
