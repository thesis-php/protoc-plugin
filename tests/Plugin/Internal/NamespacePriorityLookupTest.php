<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespacePriorityLookup::class)]
final class NamespacePriorityLookupTest extends TestCase
{
    /**
     * @param list<string> $sort
     */
    #[TestWith(['', ['php_namespace', 'package', 'parameter']])]
    #[TestWith(['parameter', ['parameter', 'php_namespace', 'package']])]
    #[TestWith(['parameter,package', ['parameter', 'package', 'php_namespace']])]
    #[TestWith(['package,parameter', ['package', 'parameter', 'php_namespace']])]
    #[TestWith(['php_namespace,parameter,package', ['php_namespace', 'parameter', 'package']])]
    public function testPriority(string $parameter, array $sort): void
    {
        self::assertSame($sort, NamespacePriorityLookup::fromString($parameter)->sort);
    }
}
