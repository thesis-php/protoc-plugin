<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Parser;

use Google\Protobuf\MethodOptions;
use Thesis\Protoc\Plugin\Comment;

/**
 * @api
 */
final readonly class ServiceMethodDescriptor
{
    public bool $bidirectionalStreaming;

    public function __construct(
        public string $name,
        public string $inType,
        public string $outType,
        public bool $clientStreaming,
        public bool $serverStreaming,
        public ?Comment $comment = null,
        public ?MethodOptions $options = null,
    ) {
        $this->bidirectionalStreaming = $this->clientStreaming && $this->serverStreaming;
    }
}
