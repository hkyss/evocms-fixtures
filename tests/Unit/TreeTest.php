<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Plan\Tree;
use PHPUnit\Framework\TestCase;

class TreeTest extends TestCase
{
    public function testWithNoFoldersEveryDocumentSitsAtTheRoot(): void
    {
        $tree = Tree::of(50, 0);

        for ($node = 1; $node <= 50; $node++) {
            $this->assertSame(0, $tree->parentOf($node));
            $this->assertSame(0, $tree->depthOf($node));
        }
    }

    public function testEveryNodeCarriesItselfAtDepthZeroAndNothingForTheRoot(): void
    {
        $rows = iterator_to_array(Tree::of(30, 5)->closure(), false);

        $selves = array_filter($rows, static fn (array $row): bool => $row[0] === $row[1]);

        $this->assertCount(30, $selves);
        $this->assertSame([], array_filter($selves, static fn (array $row): bool => $row[2] !== 0));
        $this->assertSame([], array_filter($rows, static fn (array $row): bool => $row[0] === 0));
    }

    public function testTheClosureHasARowForEveryAncestorOfEveryNode(): void
    {
        $tree = Tree::of(200, 20);
        $expected = 0;

        for ($node = 1; $node <= 200; $node++) {
            $expected += $tree->depthOf($node) + 1;
        }

        $this->assertSame($expected, count(iterator_to_array($tree->closure(), false)));
        $this->assertSame($expected, $tree->closureRows());
    }

    public function testEveryClosureRowPointsUpTheRealChain(): void
    {
        $tree = Tree::of(100, 12);

        foreach ($tree->closure() as [$ancestor, $descendant, $depth]) {
            $walk = $descendant;

            for ($step = 0; $step < $depth; $step++) {
                $walk = $tree->parentOf($walk);
            }

            $this->assertSame($ancestor, $walk, sprintf('%d is not %d steps above %d', $ancestor, $depth, $descendant));
        }
    }

    public function testContainersAreSpreadAcrossTheRangeRatherThanTakenFromTheFront(): void
    {
        $tree = Tree::of(400, 8);
        $folders = [];

        for ($node = 1; $node <= 400; $node++) {
            if ($tree->isFolder($node)) {
                $folders[] = $node;
            }
        }

        $this->assertCount(8, $folders);
        $this->assertGreaterThan(300, max($folders), 'the last container should be near the end');
        $this->assertLessThan(100, min($folders), 'the first container should be near the start');
    }

    public function testAnUncappedTreeGoesDeeperThanOneLevel(): void
    {
        $tree = Tree::of(20000, 400);

        $this->assertGreaterThan(2, $tree->deepest(), 'containers spread through the range should nest');
    }

    public function testACapIsNeverExceeded(): void
    {
        foreach ([2, 3, 5] as $cap) {
            $tree = Tree::of(5000, 200, $cap);

            $this->assertSame($cap - 1, $tree->deepest(), sprintf('a cap of %d should fill exactly %d levels', $cap, $cap));
        }
    }

    public function testACapOfOneLeavesEveryDocumentAtTheRoot(): void
    {
        $tree = Tree::of(500, 20, 1);

        $this->assertSame(0, $tree->deepest());

        for ($node = 1; $node <= 500; $node++) {
            $this->assertFalse($tree->isFolder($node));
        }
    }

    public function testAParentIsAlwaysAFolderOrTheRoot(): void
    {
        $tree = Tree::of(300, 25);

        for ($node = 1; $node <= 300; $node++) {
            $parent = $tree->parentOf($node);

            if ($parent !== 0) {
                $this->assertTrue($tree->isFolder($parent), sprintf('%d is a parent but not a folder', $parent));
            }
        }
    }

    public function testTheSameCountsAlwaysGiveTheSameTree(): void
    {
        $one = Tree::of(120, 10);
        $other = Tree::of(120, 10);

        for ($node = 1; $node <= 120; $node++) {
            $this->assertSame($one->parentOf($node), $other->parentOf($node));
        }
    }

    public function testAParentIsAlwaysAnEarlierNodeSoTheTreeCannotLoop(): void
    {
        $tree = Tree::of(150, 15);

        for ($node = 1; $node <= 150; $node++) {
            $this->assertLessThan($node, $tree->parentOf($node));
        }
    }
}
