<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Google\Protobuf\FileDescriptorProto;

/**
 * @api
 */
final readonly class Comments
{
    public const int PACKAGE_COMMENT_PATH = 2;
    public const int MESSAGE_COMMENT_PATH = 4;
    public const int ENUM_COMMENT_PATH = 5;
    public const int SERVICE_COMMENT_PATH = 6;
    public const int EXTENSION_COMMENT_PATH = 7;
    public const int SYNTAX_COMMENT_PATH = 12;
    public const int EDITION_COMMENT_PATH = 14;
    public const int MESSAGE_FIELD_COMMENT_PATH = 2;
    public const int MESSAGE_MESSAGE_COMMENT_PATH = 3;
    public const int MESSAGE_ENUM_COMMENT_PATH = 4;
    public const int MESSAGE_EXTENSION_COMMENT_PATH = 6;
    public const int ENUM_VALUE_COMMENT_PATH = 2;
    public const int SERVICE_METHOD_COMMENT_PATH = 2;

    public static function fromDescriptor(FileDescriptorProto $descriptor): self
    {
        $elements = [];

        foreach ($descriptor->sourceCodeInfo->location ?? [] as $location) {
            if ($location->leadingComments === null && $location->trailingComments === null && \count($location->leadingDetachedComments) === 0) {
                continue;
            }

            $key = implode('.', array_map(\strval(...), $location->path));

            $elements[$key] = new Comment(
                leading: self::trim($location->leadingComments ?? ''),
                trailing: self::trim($location->trailingComments ?? ''),
                detached: array_map(self::trim(...), $location->leadingDetachedComments),
            );
        }

        return new self($elements);
    }

    /**
     * @param array<string|int, Comment> $elements
     */
    public function __construct(
        public array $elements,
    ) {}

    public function comment(int|string $path): ?Comment
    {
        return $this->elements[(string) $path] ?? null;
    }

    private static function trim(string $comment): string
    {
        return trim(str_replace("\n ", "\n", $comment));
    }
}
