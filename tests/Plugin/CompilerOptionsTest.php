<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;

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
        self::assertSame($namespace, $options->phpNamespace());
    }

    #[TestWith([
        'grpc=client,grpc=server,php_namespace=App\Thesis\V1',
        true,
        true,
    ])]
    #[TestWith([
        'grpc=client,php_namespace=App\Thesis\V1',
        true,
        false,
    ])]
    #[TestWith([
        'grpc=server,php_namespace=App\Thesis\V1',
        false,
        true,
    ])]
    #[TestWith([
        'php_namespace=App\Thesis\V1',
        true,
        true,
    ])]
    #[TestWith([
        '',
        true,
        true,
    ])]
    #[TestWith([
        'grpc=none',
        false,
        false,
    ])]
    public function testGrpcOptions(string $parameter, bool $requireGrpcClient, bool $requireGrpcServer): void
    {
        $options = CompilerOptions::fromRequest(new CodeGeneratorRequest(parameter: $parameter));
        self::assertSame($requireGrpcClient, $options->requireGrpcClient());
        self::assertSame($requireGrpcServer, $options->requireGrpcServer());
    }
}
