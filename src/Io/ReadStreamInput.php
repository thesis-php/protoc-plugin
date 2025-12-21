<?php

declare(strict_types=1);

namespace Thesis\Protoc\Io;

use Amp\ByteStream;
use Thesis\Protoc\Exception\InvalidInput;
use Thesis\Protoc\ReadInput;

/**
 * @api
 */
final readonly class ReadStreamInput implements ReadInput
{
    #[\Override]
    public function read(): string
    {
        try {
            return ByteStream\buffer(ByteStream\getStdin());
        } catch (ByteStream\BufferException $e) {
            throw new InvalidInput($e->getMessage(), $e->getCode(), $e);
        }
    }
}
