<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Console\Commands;

use hkyss\Fixtures\Console\DatabaseCommand;

class ListCommand extends DatabaseCommand
{
    protected $signature = 'fixture:list
        {--database= : Connection to read}
        {--ranges : Show the id ranges each batch owns}';

    protected $description = 'What batches this installation is carrying';

    public function handle(): int
    {
        $batches = $this->ledger()->batches();

        $this->newLine();

        if ($batches === []) {
            $this->info('No batches on record.');

            return self::SUCCESS;
        }

        foreach ($batches as $batch) {
            $this->line(sprintf(
                '<options=bold>Batch %d</>  %s  <fg=gray>%s</>',
                $batch->id,
                $batch->recipe->describe(),
                date('Y-m-d H:i', $batch->createdAt)
            ));
            $this->line(sprintf('  %d rows across %d tables', $batch->rows(), count($batch->ranges)));

            if ($this->option('ranges')) {
                foreach ($batch->ranges as $range) {
                    $this->line(sprintf('    <fg=gray>%s</>', $range->describe()));
                }
            }

            $this->newLine();
        }

        $rows = array_sum(array_map(static fn ($batch): int => $batch->rows(), $batches));
        $this->line(sprintf('<options=bold>%d batch(es), %d rows.</>', count($batches), $rows));

        return self::SUCCESS;
    }
}
