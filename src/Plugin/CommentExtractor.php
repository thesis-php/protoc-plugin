<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 */
final readonly class CommentExtractor
{
    /**
     * @param ?non-empty-string $path
     */
    public function __construct(
        private Comments $comments,
        private ?string $path = null,
    ) {}

    public function extract(null|int|string $path = null): ?Comment
    {
        $ref = '';
        if ($this->path !== null) {
            $ref = "{$this->path}";
        }

        if ($path !== null) {
            if ($ref !== '') {
                $ref .= '.';
            }

            $ref .= (string) $path;
        }

        return $this->comments->comment($ref);
    }

    /**
     * @param non-empty-string $path
     */
    public function clone(string $path): self
    {
        if ($this->path !== null) {
            $path = "{$this->path}.{$path}";
        }

        return new self(
            $this->comments,
            $path,
        );
    }
}
