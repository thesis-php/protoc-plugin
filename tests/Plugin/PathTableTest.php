<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathTable::class)]
final class PathTableTest extends TestCase
{
    /**
     * @param array<string, string> $paths
     * @param array<string, list<string>> $groups
     */
    #[TestWith(
        [
            [
                'A' => 'Thesis/Api',
                'B' => 'Thesis/Api/V1',
                'C' => 'Thesis/Api/V2',
                'D' => 'Thesis/Api/Common',
                'X' => 'Typhoon/Analyzer',
                'Y' => 'Typhoon/Analyzer/Ir',
                'L' => 'Ydb/Auth',
                'R' => 'Ydb/Topic',
            ],
            [
                'Thesis\Api' => ['A', 'B', 'C', 'D'],
                'Typhoon\Analyzer' => ['X', 'Y'],
                'Ydb' => ['L', 'R'],
            ],
        ],
    )]
    #[TestWith(
        [
            [],
            [],
        ],
    )]
    public function testGroupByNamespace(array $paths, array $groups): void
    {
        $table = new PathTable();

        foreach ($paths as $fqcn => $path) {
            $table->addRelation($fqcn, $path);
        }

        self::assertSame($groups, $table->groupByNamespace());
    }
}
