<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Protoc\Plugin\Naming;

#[CoversClass(Naming::class)]
final class NamingTest extends TestCase
{
    #[TestWith(['foo', 'foo'])]
    #[TestWith(['foo_bar', 'fooBar'])]
    #[TestWith(['Foo_Bar', 'fooBar'])]
    public function testCamelCase(string $actual, string $expected): void
    {
        self::assertSame($expected, Naming::camelCase($actual));
    }

    #[TestWith(['foo', 'Foo'])]
    #[TestWith(['foo_bar', 'FooBar'])]
    #[TestWith(['Foo_Bar', 'FooBar'])]
    #[TestWith(['Empty', 'Empty_'])]
    #[TestWith(['True', 'True_'])]
    public function testPascalCase(string $actual, string $expected): void
    {
        self::assertSame($expected, Naming::pascalCase($actual));
    }

    #[TestWith(['test.api.v1', 'Test\Api\V1'])]
    #[TestWith(['lower_api_name', 'LowerApiName'])]
    #[TestWith(['lower_api.name', 'LowerApi\Name'])]
    #[TestWith(['TeSt.aPI.v1', 'TeSt\API\V1'])]
    public function testNamespace(string $actual, string $expected): void
    {
        self::assertSame($expected, Naming::namespace($actual));
    }

    /**
     * @param list<string> $paths
     */
    #[TestWith([['test', 'api', 'v1'], 'Test\Api\V1'])]
    #[TestWith([['lower_api', 'v1'], 'LowerApi\V1'])]
    #[TestWith([['lower.api', 'v1'], 'Lower\Api\V1'])]
    public function testJoinNamespace(array $paths, string $expected): void
    {
        self::assertSame($expected, Naming::joinNamespace($paths));
    }

    #[TestWith(['test.api.v1', 'Test/Api/V1'])]
    #[TestWith(['lower_api.v1', 'LowerApi/V1'])]
    public function testPath(string $actual, string $expected): void
    {
        self::assertSame($expected, Naming::path($actual));
    }

    #[TestWith(['TestRequest', 'TestRequest'])]
    #[TestWith(['Class', 'Class_'])]
    public function testSecure(string $actual, string $expected): void
    {
        self::assertSame($expected, Naming::secure($actual));
    }
}
