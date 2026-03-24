<?php

declare(strict_types=1);

namespace Thesis\Protoc\Io;

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
        $contents = stream_get_contents(\STDIN);

        if ($contents === false) {
            throw new InvalidInput('Failed to read from STDIN');
        }

        return $contents;
    }
}
