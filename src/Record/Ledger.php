<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Record;

use hkyss\Fixtures\Plan\Recipe;
use Illuminate\Database\Connection;

class Ledger
{
    public const BATCHES = 'fixture_batches';
    public const RANGES = 'fixture_ranges';

    private bool $ensured = false;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function exists(): bool
    {
        return $this->tableExists(self::BATCHES);
    }

    public function ensure(): void
    {
        if ($this->ensured) {
            return;
        }

        $collation = $this->prevailingCollation();
        $suffix = $collation !== null ? sprintf(' COLLATE=%s', $collation) : '';

        $this->connection->statement(sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` ('
            . '`id` int unsigned NOT NULL AUTO_INCREMENT,'
            . '`recipe` text NOT NULL,'
            . '`created_at` int unsigned NOT NULL,'
            . 'PRIMARY KEY (`id`)'
            . ') ENGINE=InnoDB%s',
            $this->qualify(self::BATCHES),
            $suffix
        ));

        $this->connection->statement(sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` ('
            . '`id` int unsigned NOT NULL AUTO_INCREMENT,'
            . '`batch_id` int unsigned NOT NULL,'
            . '`table_name` varchar(191) NOT NULL,'
            . '`key_column` varchar(191) NOT NULL,'
            . '`first_key` bigint NOT NULL,'
            . '`last_key` bigint NOT NULL,'
            . '`rows_written` int unsigned NOT NULL,'
            . 'PRIMARY KEY (`id`),'
            . 'KEY `fixture_ranges_batch` (`batch_id`)'
            . ') ENGINE=InnoDB%s',
            $this->qualify(self::RANGES),
            $suffix
        ));

        $this->ensured = true;
    }

    public function open(Recipe $recipe): int
    {
        $this->ensure();

        return (int) $this->connection->table(self::BATCHES)->insertGetId([
            'recipe' => (string) json_encode($recipe->toArray()),
            'created_at' => time(),
        ]);
    }

    public function note(int $batch, Range $range): void
    {
        if ($range->isEmpty()) {
            return;
        }

        $this->connection->table(self::RANGES)->insert([
            'batch_id' => $batch,
            'table_name' => $range->table,
            'key_column' => $range->keyColumn,
            'first_key' => $range->first,
            'last_key' => $range->last,
            'rows_written' => $range->rows,
        ]);
    }

    /** @return list<Batch> */
    public function batches(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $batches = [];

        foreach ($this->connection->table(self::BATCHES)->orderByDesc('id')->get() as $row) {
            $id = (int) $row->id;
            $decoded = json_decode((string) $row->recipe, true);

            $batches[] = new Batch(
                $id,
                Recipe::fromArray(is_array($decoded) ? $decoded : []),
                $this->rangesOf($id),
                (int) $row->created_at
            );
        }

        return $batches;
    }

    public function find(int $id): ?Batch
    {
        foreach ($this->batches() as $batch) {
            if ($batch->id === $id) {
                return $batch;
            }
        }

        return null;
    }

    public function close(int $batch): void
    {
        $this->connection->table(self::RANGES)->where('batch_id', $batch)->delete();
        $this->connection->table(self::BATCHES)->where('id', $batch)->delete();

        if ((int) $this->connection->table(self::BATCHES)->count() === 0) {
            $this->discard();
        }
    }

    public function discard(): void
    {
        $this->connection->statement(sprintf('DROP TABLE IF EXISTS `%s`', $this->qualify(self::RANGES)));
        $this->connection->statement(sprintf('DROP TABLE IF EXISTS `%s`', $this->qualify(self::BATCHES)));
        $this->ensured = false;
    }

    public function qualify(string $table): string
    {
        return $this->connection->getTablePrefix() . $table;
    }

    /** @return list<Range> */
    private function rangesOf(int $batch): array
    {
        $ranges = [];

        foreach ($this->connection->table(self::RANGES)->where('batch_id', $batch)->orderBy('id')->get() as $row) {
            $ranges[] = new Range(
                (string) $row->table_name,
                (string) $row->key_column,
                (int) $row->first_key,
                (int) $row->last_key,
                (int) $row->rows_written
            );
        }

        return $ranges;
    }

    private function tableExists(string $table): bool
    {
        $row = $this->connection->selectOne(
            'SELECT COUNT(*) AS `present` FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$this->qualify($table)]
        );

        return is_object($row) && ((int) ($row->present ?? 0)) > 0;
    }

    private function prevailingCollation(): ?string
    {
        $row = $this->connection->selectOne(
            'SELECT TABLE_COLLATION AS `found`, COUNT(*) AS `tables` FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION IS NOT NULL'
            . ' GROUP BY TABLE_COLLATION ORDER BY `tables` DESC, `found` ASC LIMIT 1'
        );

        return is_object($row) && is_string($row->found ?? null) ? $row->found : null;
    }
}
