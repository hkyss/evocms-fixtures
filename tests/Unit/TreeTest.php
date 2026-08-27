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

    public function testOnlyTheFirstNodesOpenAsContainers(): void
    {
        $tree = Tree::of(40, 6);

        $this->assertTrue($tree->isFolder(6));
        $this->assertFalse($tree->isFolder(7));
        $this->assertFalse($tree->isFolder(40));
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
