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

    public static function fromRequest(CodeGeneratorRequest $request): self
    {
        $parameters = [];

        foreach (explode(',', $request->parameter ?? '') as $parameter) {
            $option = explode('=', $parameter);
            if (\count($option) === 2) {
                $parameters[$option[0]] = $option[1];
            }
        }

        return new self($parameters);
    }

    /**
     * @param array<string, string> $options
     */
    private function __construct(
        private array $options,
    ) {}

    public function phpNamespace(): ?string
    {
        return $this->doGetString(self::OPTION_PHP_NAMESPACE);
    }

    /**
     * @param self::OPTION_* $name
     */
    private function doGetString(string $name): ?string
    {
        return $this->options[$name] ?? null;
    }
}
