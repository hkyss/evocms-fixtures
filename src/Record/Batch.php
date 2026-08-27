<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Record;

use hkyss\Fixtures\Plan\Recipe;

final class Batch
{
    /** @param list<Range> $ranges */
    public function __construct(
        public readonly int $id,
        public readonly Recipe $recipe,
        public readonly array $ranges,
        public readonly int $createdAt,
    ) {
    }

    public function rows(): int
    {
        return array_sum(array_map(static fn (Range $range): int => $range->rows, $this->ranges));
    }

    /** @param list<Range> $ranges */
    public function with(array $ranges): self
    {
        return new self($this->id, $this->recipe, $ranges, $this->createdAt);
    }
}
