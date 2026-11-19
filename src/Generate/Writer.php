<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Generate;

use Closure;
use Illuminate\Database\Connection;

final class Writer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly int $chunk = 500,
    ) {
    }

    /** @param iterable<array<string, mixed>> $rows */
    public function into(string $table, iterable $rows, ?Closure $before = null): int
    {
        $written = 0;
        $buffer = [];

        foreach ($rows as $row) {
            $buffer[] = $row;

            if (count($buffer) >= $this->chunk) {
                $before !== null && $before($buffer);
                $this->connection->table($table)->insert($buffer);
                $written += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $before !== null && $before($buffer);
            $this->connection->table($table)->insert($buffer);
            $written += count($buffer);
        }

        return $written;
    }
}
