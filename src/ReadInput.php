<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Thesis\Protoc\Exception\InvalidInput;

/**
 * @api
 */
interface ReadInput
{
    /**
     * @throws InvalidInput
     */
    public function read(): string;
}
