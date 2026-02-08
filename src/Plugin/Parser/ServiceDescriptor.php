<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class ServiceDescriptor
{
    /**
     * @param list<ServiceMethodDescriptor> $methods
     */
    public function __construct(
        public string $name,
        public string $path,
        public array $methods,
        public ?Comment $comment = null,
    ) {}
}
