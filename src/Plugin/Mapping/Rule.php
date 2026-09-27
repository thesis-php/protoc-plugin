<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Mapping;

/**
 * A single "from → to" relocation of protobuf fully qualified names.
 *
 * Rules never rename a type: only the package (and therefore the PHP namespace)
 * a type lives in is rewritten.
 *
 * @api
 */
interface Rule
{
    /**
     * The name the rule matches, exactly as it is written in the mapping file.
     *
     * @return non-empty-string
     */
    public function declaration(): string;

    /**
     * Rewrites a fully qualified protobuf type name, e.g. "google.protobuf.Any".
     *
     * @param string $type type name without the leading dot
     * @return ?non-empty-string rewritten name or null when the rule does not match
     */
    public function rewriteType(string $type): ?string;

    /**
     * Rewrites a protobuf package name, e.g. "google.protobuf".
     *
     * @return ?non-empty-string rewritten package or null when the rule does not match
     */
    public function rewritePackage(string $package): ?string;

    /**
     * The more specific the rule is, the earlier it is applied: an exact rule always
     * wins over a pattern, and a longer pattern wins over a shorter one.
     */
    public function priority(): int;
}
