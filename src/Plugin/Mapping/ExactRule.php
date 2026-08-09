<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Mapping;

/**
 * Relocates exactly one type: "google.protobuf.Any" → "thesis.google.protobuf.Any".
 *
 * @api
 */
final readonly class ExactRule implements Rule
{
    /**
     * @param non-empty-string $from
     * @param non-empty-string $to
     */
    public function __construct(
        public string $from,
        public string $to,
    ) {}

    #[\Override]
    public function declaration(): string
    {
        return $this->from;
    }

    #[\Override]
    public function rewriteType(string $type): ?string
    {
        return $type === $this->from ? $this->to : null;
    }

    #[\Override]
    public function rewritePackage(string $package): ?string
    {
        return null;
    }

    #[\Override]
    public function priority(): int
    {
        return 2 * \strlen($this->from) + 1;
    }
}
