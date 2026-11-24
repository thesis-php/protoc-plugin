<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpNamespacer::class)]
final class PhpNamespacerTest extends TestCase
{
    /**
     * The namespace of the file is a PHP namespace already, so only the protobuf segments of the path are normalised.
     */
    #[TestWith(['Foo_Bar\baz', 'Outer', 'Foo_Bar\baz', '\Foo_Bar\baz\Outer'])]
    #[TestWith(['Foo_Bar\baz', 'Outer.Inner', 'Foo_Bar\baz\Outer', '\Foo_Bar\baz\Outer\Inner'])]
    #[TestWith(['Foo_Bar\baz', 'Outer.Inner.Deep', 'Foo_Bar\baz\Outer\Inner', '\Foo_Bar\baz\Outer\Inner\Deep'])]
    #[TestWith(['Foo_Bar\baz', 'outer_msg.inner_msg', 'Foo_Bar\baz\OuterMsg', '\Foo_Bar\baz\OuterMsg\InnerMsg'])]
    #[TestWith(['app\v1', 'Outer.Choice', 'app\v1\Outer', '\app\v1\Outer\Choice'])]
    #[TestWith(['App\V1', 'Outer.Inner', 'App\V1\Outer', '\App\V1\Outer\Inner'])]
    public function testKeepsFileNamespaceVerbatim(string $namespace, string $path, string $expectedNamespace, string $expectedFqcn): void
    {
        $namespacer = new PhpNamespacer($namespace);

        self::assertSame($expectedNamespace, $namespacer->create($path)->getName());
        self::assertSame($expectedFqcn, $namespacer->fqcn($path));
    }
}
