<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protoc\Plugin\Generator\DescriptorFile;

/**
 * Accumulates every .proto file of a single package (PHP namespace) so the
 * compiler can emit one DescriptorRegistry per package instead of one per file.
 *
 * @api
 */
final class DescriptorRegistryGroup
{
    /** @var list<string> */
    public private(set) array $sources = [];

    /** @var array<string, true> */
    public private(set) array $taken = [];

    /** @var list<DescriptorFile> */
    public private(set) array $files = [];

    public function __construct(
        public string $namespace,
        public string $path,
    ) {}

    /**
     * @param array<string, true> $taken class names already used in the namespace
     */
    public function add(string $source, array $taken, DescriptorFile $file): void
    {
        $this->sources[] = $source;
        $this->taken += $taken;
        $this->files[] = $file;
    }
}
