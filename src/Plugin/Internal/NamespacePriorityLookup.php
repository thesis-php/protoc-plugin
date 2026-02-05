<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Internal;

/**
 * @internal
 */
final readonly class NamespacePriorityLookup
{
    public const string NAMESPACE_LOOKUP_PHP_NAMESPACE = 'php_namespace';
    public const string NAMESPACE_LOOKUP_PACKAGE = 'package';
    public const string NAMESPACE_LOOKUP_PARAMETER = 'parameter';
    private const array DEFAULT_NAMESPACE_LOOKUP_PRIORITY = [
        self::NAMESPACE_LOOKUP_PHP_NAMESPACE => 100,
        self::NAMESPACE_LOOKUP_PACKAGE => 200,
        self::NAMESPACE_LOOKUP_PARAMETER => 300,
    ];

    /** @var list<string> */
    public array $sort;

    public static function fromString(string $parameter): self
    {
        $sort = array_filter(
            explode(',', $parameter),
            static fn(string $v) => $v !== '',
        );
        if ($sort !== []) {
            $sort = array_combine(
                $sort,
                range(1, \count($sort)),
            );
        }

        return new self($sort);
    }

    /**
     * @param array<string, int> $priority
     */
    public function __construct(array $priority = [])
    {
        $priority += self::DEFAULT_NAMESPACE_LOOKUP_PRIORITY;
        uasort($priority, static fn(int $a, int $b) => $a <=> $b);

        $this->sort = array_keys($priority);
    }
}
