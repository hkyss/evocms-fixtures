<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Plan;

use Generator;

final class Tree
{
    /** @var array<int, int> */
    private array $parents;

    /** @var array<int, bool> */
    private array $folders;

    /** @var array<int, int> */
    private array $depths;

    /**
     * @param array<int, int>  $parents
     * @param array<int, bool> $folders
     * @param array<int, int>  $depths
     */
    private function __construct(private readonly int $size, array $parents, array $folders, array $depths)
    {
        $this->parents = $parents;
        $this->folders = $folders;
        $this->depths = $depths;
    }

    // Containers are spread across the whole range rather than taken from the front. Opened at
    // the front they would all hang off the root and everything else would sit one level under
    // them, which is a wide list wearing a tree's clothes.
    public static function of(int $documents, int $folders, int $maxDepth = 0): self
    {
        $pool = [0];
        $parents = [0 => 0];
        $depths = [0 => -1];
        $isFolder = [];
        $every = $folders > 0 ? max(1, intdiv($documents, $folders)) : 0;
        $opened = 0;
        $seed = 2463534242;

        for ($node = 1; $node <= $documents; $node++) {
            // Not the node number: containers land on exact multiples of the step, and the pool
            // is as large as the containers opened so far, so that product is always divisible
            // by the modulus and every container would come out a child of the root.
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $parent = $pool[$seed % count($pool)];
            $parents[$node] = $parent;
            $depths[$node] = $depths[$parent] + 1;

            $room = $maxDepth < 1 || $depths[$node] < $maxDepth - 1;
            $isFolder[$node] = $room && $opened < $folders && $every > 0 && ($node % $every) === 0;

            if ($isFolder[$node]) {
                $pool[] = $node;
                $opened++;
            }
        }

        return new self($documents, $parents, $isFolder, $depths);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function parentOf(int $node): int
    {
        return $this->parents[$node] ?? 0;
    }

    public function isFolder(int $node): bool
    {
        return $this->folders[$node] ?? false;
    }

    public function depthOf(int $node): int
    {
        return $this->depths[$node] ?? 0;
    }

    public function deepest(): int
    {
        return $this->size > 0 ? max(array_slice($this->depths, 1, null, true)) : 0;
    }

    /** @return Generator<int, array{int, int, int}> */
    public function closure(): Generator
    {
        for ($node = 1; $node <= $this->size; $node++) {
            $depth = 0;

            for ($ancestor = $node; $ancestor !== 0; $ancestor = $this->parentOf($ancestor)) {
                yield [$ancestor, $node, $depth++];
            }
        }
    }

    public function closureRows(): int
    {
        $rows = 0;

        for ($node = 1; $node <= $this->size; $node++) {
            $rows += $this->depthOf($node) + 1;
        }

        return $rows;
    }
}
