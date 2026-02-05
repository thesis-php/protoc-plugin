<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;

/**
 * @api
 */
final readonly class CompilerOptions
{
    private const string OPTION_PHP_NAMESPACE = 'php_namespace';
    private const string OPTION_PHP_NAMESPACE_PRIORITY = 'namespace_priority';
    private const string OPTION_SRC_PATH = 'src_path';
    private const string OPTION_GRPC = 'grpc';
    private const string GRPC_OPTION_CLIENT = 'client';
    private const string GRPC_OPTION_SERVER = 'server';
    private const array GRPC_OPTIONS = [
        self::GRPC_OPTION_CLIENT,
        self::GRPC_OPTION_SERVER,
    ];

    public static function fromRequest(CodeGeneratorRequest $request): self
    {
        $parameters = [];

        foreach (explode(',', $request->parameter ?? '') as $parameter) {
            $option = explode('=', $parameter);
            if (\count($option) === 2) {
                $parameters[$option[0]][] = $option[1];
            }
        }

        return new self($parameters);
    }

    /**
     * @param array<string, list<string>> $options
     */
    private function __construct(
        private array $options,
    ) {}

    public function phpNamespace(): ?string
    {
        return $this->doGetString(self::OPTION_PHP_NAMESPACE);
    }

    public function namespacePriority(): string
    {
        return implode(',', $this->doGetArray(self::OPTION_PHP_NAMESPACE_PRIORITY));
    }

    public function srcPath(): ?string
    {
        return $this->doGetString(self::OPTION_SRC_PATH);
    }

    public function requireGrpcClient(): bool
    {
        return $this->contains(self::OPTION_GRPC, self::GRPC_OPTION_CLIENT, self::GRPC_OPTIONS);
    }

    public function requireGrpcServer(): bool
    {
        return $this->contains(self::OPTION_GRPC, self::GRPC_OPTION_SERVER, self::GRPC_OPTIONS);
    }

    /**
     * @param self::OPTION_* $name
     * @param non-empty-string $value
     * @param list<string> $defaults
     */
    public function contains(string $name, string $value, array $defaults = []): bool
    {
        return array_any(
            $this->options[$name] ?? $defaults,
            static fn(string $it) => $it === $value,
        );
    }

    /**
     * @param self::OPTION_* $name
     */
    private function doGetString(string $name): ?string
    {
        $values = $this->options[$name] ?? [];

        return $values[0] ?? null;
    }

    /**
     * @param self::OPTION_* $name
     * @return list<string>
     */
    private function doGetArray(string $name): array
    {
        return $this->options[$name] ?? [];
    }
}
