<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\Compiler\CodeGeneratorRequest;

/**
 * @api
 */
final readonly class CompilerOptions
{
    private const string OPTION_PHP_NAMESPACE = 'php_namespace';
    private const string OPTION_SRC_PATH = 'src_path';
    private const string OPTION_GRPC = 'grpc';
    private const string GRPC_OPTION_CLIENT = 'client';
    private const string GRPC_OPTION_SERVER = 'server';

    public static function fromRequest(CodeGeneratorRequest $request): self
    {
        $parameters = [];

        foreach (explode(',', $request->parameter ?? '') as $parameter) {
            $option = explode('=', $parameter);
            if (\count($option) === 2) {
                $parameters[$option[0]][] = $option[1];
            }
        }

        $grpc = $parameters[self::OPTION_GRPC] ?? [
            self::GRPC_OPTION_CLIENT,
            self::GRPC_OPTION_SERVER,
        ];

        $requireGrpcClient = array_any($grpc, static fn(string $target) => $target === self::GRPC_OPTION_CLIENT);
        $requireGrpcServer = array_any($grpc, static fn(string $target) => $target === self::GRPC_OPTION_SERVER);

        return new self(
            requireGrpcClient: $requireGrpcClient,
            requireGrpcServer: $requireGrpcServer,
            phpNamespace: self::doGetString($parameters, self::OPTION_PHP_NAMESPACE),
            srcPath: self::doGetString($parameters, self::OPTION_SRC_PATH),
        );
    }

    /**
     * @param ?non-empty-string $phpNamespace
     * @param ?non-empty-string $srcPath
     */
    private function __construct(
        public bool $requireGrpcClient,
        public bool $requireGrpcServer,
        public ?string $phpNamespace = null,
        public ?string $srcPath = null,
    ) {}

    /**
     * @param array<string, list<string>> $parameters
     * @param self::OPTION_* $name
     * @return ?non-empty-string
     */
    private static function doGetString(array $parameters, string $name): ?string
    {
        $values = $parameters[$name] ?? [];
        $value = $values[0] ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        return null;
    }
}
