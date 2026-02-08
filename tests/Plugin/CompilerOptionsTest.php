<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\Compiler\CodeGeneratorRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompilerOptions::class)]
final class CompilerOptionsTest extends TestCase
{
    #[TestWith([
        'grpc=client,grpc=server,php_namespace=App\Thesis\V1',
        'App\Thesis\V1',
    ])]
    #[TestWith([
        'php_namespace=App\Thesis\V1',
        'App\Thesis\V1',
    ])]
    #[TestWith([
        'grpc=client,grpc=server',
        null,
    ])]
    public function testPhpNamespaceOption(string $parameter, ?string $namespace = null): void
    {
        $options = CompilerOptions::fromRequest(new CodeGeneratorRequest(parameter: $parameter));
        self::assertSame($namespace, $options->phpNamespace);
    }
}
