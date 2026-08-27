<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Record;

final class Range
{
    public function __construct(
        public readonly string $table,
        public readonly string $keyColumn,
        public readonly int $first,
        public readonly int $last,
        public readonly int $rows,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->rows === 0 || $this->last < $this->first;
    }

    public function describe(): string
    {
        return sprintf('%s %s %d..%d (%d rows)', $this->table, $this->keyColumn, $this->first, $this->last, $this->rows);
    }
}
