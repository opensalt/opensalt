<?php

declare(strict_types=1);

namespace Tests\Unit\App\Service\Markdown;

use App\Service\Markdown\PackageTreeBuilder;

class PackageTreeBuilderTest extends \Codeception\Test\Unit
{
    public function testOrdersHierarchyWithNumbers(): void
    {
        $tocRows = [
            ['identifier' => 'c', 'title' => 'Child B', 'code' => null, 'listEnum' => '2'],
            ['identifier' => 'a', 'title' => 'Root A', 'code' => 'A', 'listEnum' => '1'],
            ['identifier' => 'b', 'title' => 'Child A', 'code' => null, 'listEnum' => '1'],
            ['identifier' => 'd', 'title' => 'Root B', 'code' => null, 'listEnum' => '2'],
        ];
        $parentMap = ['b' => 'a', 'c' => 'a'];

        $ordered = (new PackageTreeBuilder())->order($tocRows, $parentMap);

        self::assertSame(
            ['a', 'b', 'c', 'd'],
            array_column($ordered, 'identifier')
        );
        self::assertSame(['1', '1.1', '1.2', '2'], array_column($ordered, 'number'));
        self::assertSame([0, 1, 1, 0], array_column($ordered, 'depth'));
    }

    public function testCycleDoesNotLoopForever(): void
    {
        $tocRows = [
            ['identifier' => 'x', 'title' => 'X', 'code' => null, 'listEnum' => null],
            ['identifier' => 'y', 'title' => 'Y', 'code' => null, 'listEnum' => null],
        ];
        $parentMap = ['x' => 'y', 'y' => 'x'];

        $ordered = (new PackageTreeBuilder())->order($tocRows, $parentMap);

        self::assertCount(2, $ordered);
    }
}
