<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Bench;

use Illuminate\Database\Connection;
use InvalidArgumentException;

class Bench
{
    private const POOL = 200;

    public function __construct(
        private readonly Connection $connection,
        private readonly int $runs = 7,
    ) {
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $pool = $this->pool();

        if ($pool === []) {
            return ['ready' => false, 'why' => 'There is no content to read. Generate a batch first.', 'probes' => []];
        }

        $probes = [];

        foreach (Suite::evolution($this->connection->getTablePrefix()) as $probe) {
            $probes[] = [
                'name' => $probe->name,
                'reads' => $probe->reads,
                'median' => $this->time($probe, $pool),
            ];
        }

        return ['ready' => true, 'counts' => $this->counts(), 'runs' => $this->runs, 'probes' => $probes];
    }

    /** @param list<array<string, mixed>> $pool */
    private function time(Probe $probe, array $pool): float
    {
        $statement = $this->connection->getPdo()->prepare($probe->sql);
        $times = [];

        for ($run = 0; $run <= $this->runs; $run++) {
            $binds = $this->bind($probe, $pool[array_rand($pool)]);
            $started = hrtime(true);
            $statement->execute($binds);
            $statement->fetchAll();
            $statement->closeCursor();

            if ($run > 0) {
                $times[] = (hrtime(true) - $started) / 1e6;
            }
        }

        sort($times);

        return round($times[intdiv(count($times), 2)], 3);
    }

    /**
     * @param  array<string, mixed> $sample
     * @return list<mixed>
     */
    private function bind(Probe $probe, array $sample): array
    {
        $values = [];

        foreach ($probe->binds as $bind) {
            $values[] = match ($bind) {
                'document' => (int) $sample['id'],
                'folder' => (int) ($sample['isfolder'] ? $sample['id'] : $sample['parent']),
                'parent' => (int) $sample['parent'],
                'alias' => (string) $sample['alias'],
                'template' => (int) $sample['template'],
                default => throw new InvalidArgumentException(sprintf('No sample answers to %s.', $bind)),
            };
        }

        return $values;
    }

    /** @return list<array<string, mixed>> */
    private function pool(): array
    {
        $bounds = $this->connection->selectOne(sprintf(
            'SELECT MIN(`id`) AS `low`, MAX(`id`) AS `high` FROM `%s`',
            $this->connection->getTablePrefix() . 'site_content'
        ));

        if (!is_object($bounds) || $bounds->low === null) {
            return [];
        }

        $low = (int) $bounds->low;
        $high = (int) $bounds->high;
        $from = $high > $low ? random_int($low, max($low, $high - self::POOL)) : $low;

        $rows = $this->connection->select(sprintf(
            'SELECT `id`, `alias`, `parent`, `template`, `isfolder` FROM `%s` WHERE `id` >= ? ORDER BY `id` LIMIT %d',
            $this->connection->getTablePrefix() . 'site_content',
            self::POOL
        ), [$from]);

        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        $counts = [];

        foreach (['site_content', 'site_content_closure', 'site_tmplvar_contentvalues', 'site_tmplvars'] as $table) {
            $counts[$table] = (int) $this->connection->table($table)->count();
        }

        return $counts;
    }
}
