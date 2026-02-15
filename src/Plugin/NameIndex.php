<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final class NameIndex implements \Countable
{
    /** @var array<string, string> */
    public private(set) array $messageTypes = [];

    /** @var array<string, string> */
    public private(set) array $enumTypes = [];

    public function addMessageType(string $type, string $fqcn): void
    {
        $this->messageTypes[$type] = $fqcn;
    }

    public function addEnumType(string $type, string $fqcn): void
    {
        $this->enumTypes[$type] = $fqcn;
    }

    public function empty(): bool
    {
        return $this->messageTypes === [] && $this->enumTypes === [];
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->messageTypes) + \count($this->enumTypes);
    }
}
