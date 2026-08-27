<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Console\Commands;

use hkyss\Fixtures\Console\DatabaseCommand;
use hkyss\Fixtures\Record\Batch;

class DropCommand extends DatabaseCommand
{
    protected $signature = 'fixture:drop
        {batch? : Which batch to remove; the newest when omitted}
        {--all : Remove every batch on record}
        {--database= : Connection to change}
        {--dry-run : Say what would go and remove nothing}
        {--force : Skip the confirmation}';

    protected $description = 'Remove a generated batch, and nothing else';

    public function handle(): int
    {
        $batches = $this->ledger()->batches();

        $this->newLine();

        if ($batches === []) {
            $this->info('No batches on record.');

            return self::SUCCESS;
        }

        $chosen = $this->chosen($batches);

        if ($chosen === []) {
            $this->error(sprintf('No batch %s on record.', (string) $this->argument('batch')));

            return self::FAILURE;
        }

        foreach ($chosen as $batch) {
            $this->line(sprintf('<options=bold>Batch %d</> — %d rows', $batch->id, $batch->rows()));

            foreach ($batch->ranges as $range) {
                $this->line(sprintf('  <fg=gray>%s</>', $range->describe()));
            }
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing was removed.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(sprintf('Remove %d batch(es)?', count($chosen)), false)) {
            $this->line('Nothing was removed.');

            return self::SUCCESS;
        }

        $removed = 0;

        foreach ($chosen as $batch) {
            $removed += $this->generator()->drop($batch, function (string $table, int $rows): void {
                $this->line(sprintf('  <fg=green>%8d</> %s', $rows, $table));
            });
        }

        $this->newLine();
        $this->line(sprintf('<options=bold>%d row(s) removed.</>', $removed));

        return self::SUCCESS;
    }

    /**
     * @param  list<Batch> $batches
     * @return list<Batch>
     */
    private function chosen(array $batches): array
    {
        if ($this->option('all')) {
            return $batches;
        }

        $asked = $this->argument('batch');

        if ($asked === null) {
            return [$batches[0]];
        }

        return array_values(array_filter($batches, static fn (Batch $batch): bool => $batch->id === (int) $asked));
    }
}
