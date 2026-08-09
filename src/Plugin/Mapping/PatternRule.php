<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Mapping;

/**
 * Relocates a whole package with everything nested in it:
 * "google.protobuf.*" → "thesis.google.protobuf.*".
 *
 * The package itself matches as well, so both the package "google.protobuf" and
 * the type "google.protobuf.Struct.FieldsEntry" are rewritten.
 *
 * @api
 */
final readonly class PatternRule implements Rule
{
    public const string SUFFIX = '.*';

    /**
     * @param non-empty-string $from package prefix without the trailing ".*"
     * @param non-empty-string $to package prefix without the trailing ".*"
     */
    public function __construct(
        public string $from,
        public string $to,
    ) {}

    #[\Override]
    public function declaration(): string
    {
        return $this->from . self::SUFFIX;
    }

    #[\Override]
    public function rewriteType(string $type): ?string
    {
        return $this->rewrite($type);
    }

    #[\Override]
    public function rewritePackage(string $package): ?string
    {
        return $this->rewrite($package);
    }

    #[\Override]
    public function priority(): int
    {
        return 2 * \strlen($this->from);
    }

    /**
     * @return ?non-empty-string
     */
    private function rewrite(string $name): ?string
    {
        if ($name === $this->from) {
            return $this->to;
        }

        if (str_starts_with($name, "{$this->from}.")) {
            return $this->to . substr($name, \strlen($this->from));
        }

        return null;
    }
}
