<?php

declare(strict_types=1);

namespace Thesis\Protoc\Io;

use Amp\ByteStream;
use Thesis\Protoc\WriteOutput;

/**
 * @api
 */
final readonly class WriteStreamOutput implements WriteOutput
{
    #[\Override]
    public function write(string $buffer): void
    {
        ByteStream\getStdout()->write($buffer);
    }
}
