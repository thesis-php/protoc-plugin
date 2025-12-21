<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final readonly class Comment implements \Stringable
{
    /**
     * @param list<string> $detached
     */
    public function __construct(
        public string $leading,
        public string $trailing,
        public array $detached,
    ) {}

    public function __toString(): string
    {
        $buffer = '';

        if ($this->leading !== '') {
            $buffer .= "{$this->leading}\n\n";
        }

        $buffer .= $this->trailing;

        return trim($buffer);
    }
}
