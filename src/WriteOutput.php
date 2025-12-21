<?php

declare(strict_types=1);

namespace Thesis\Protoc;

/**
 * @api
 */
interface WriteOutput
{
    /**
     * @param non-empty-string $buffer
     */
    public function write(string $buffer): void;
}
