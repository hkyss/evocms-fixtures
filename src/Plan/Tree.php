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

    /**
     * @param array<int, int>  $parents
     * @param array<int, bool> $folders
     */
    private function __construct(private readonly int $size, array $parents, array $folders)
    {
        $this->parents = $parents;
        $this->folders = $folders;
    }

    public static function of(int $documents, int $folders): self
    {
        $pool = [0];
        $parents = [0 => 0];
        $isFolder = [];

        for ($node = 1; $node <= $documents; $node++) {
            $parents[$node] = $pool[($node * 7919) % count($pool)];
            $isFolder[$node] = $node <= $folders;

            if ($isFolder[$node]) {
                $pool[] = $node;
            }
        }

        return new self($documents, $parents, $isFolder);
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
        $depth = 0;

        for ($walk = $this->parentOf($node); $walk !== 0; $walk = $this->parentOf($walk)) {
            $depth++;
        }

        return $depth;
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
