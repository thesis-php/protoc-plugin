<?php

declare(strict_types=1);

namespace Thesis\Protoc\Io;

use Thesis\Protoc\WriteOutput;

/**
 * @api
 */
final readonly class WriteStreamOutput implements WriteOutput
{
    #[\Override]
    public function write(string $buffer): void
    {
        \fwrite(\STDOUT, $buffer);
    }
}
