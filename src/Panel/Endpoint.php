<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Panel;

use hkyss\Fixtures\Bench\Bench;
use hkyss\Fixtures\Generate\Generator;
use hkyss\Fixtures\Plan\Recipe;
use hkyss\Fixtures\Record\Batch;
use hkyss\Fixtures\Record\Ledger;
use InvalidArgumentException;
use Throwable;

class Endpoint
{
    public function __construct(
        private readonly Ledger $ledger,
        private readonly Generator $generator,
        private readonly Bench $bench,
        private readonly Config $config,
    ) {
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function handle(string $action, array $payload): array
    {
        try {
            return match ($action) {
                'state' => $this->state(),
                'make' => $this->make($payload),
                'drop' => $this->drop($payload),
                'bench' => ['ok' => true, 'bench' => $this->bench->run()],
                default => ['ok' => false, 'why' => 'unknown action'],
            };
        } catch (InvalidArgumentException $e) {
            return ['ok' => false, 'why' => $e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'why' => $e->getMessage()];
        }
    }

    /** @return array<string, mixed> */
    private function state(): array
    {
        return [
            'ok' => true,
            'ceiling' => $this->config->maxDocuments,
            'batches' => array_map(static fn (Batch $batch): array => [
                'id' => $batch->id,
                'describe' => $batch->recipe->describe(),
                'rows' => $batch->rows(),
                'tables' => count($batch->ranges),
                'created' => $batch->createdAt,
            ], $this->ledger->batches()),
        ];
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function make(array $payload): array
    {
        $documents = (int) ($payload['documents'] ?? 0);

        if ($documents > $this->config->maxDocuments) {
            return ['ok' => false, 'why' => sprintf(
                'The panel writes at most %d documents in one go; a bigger batch belongs on the console, where nothing times out.',
                $this->config->maxDocuments
            )];
        }

        $batch = $this->generator->make(new Recipe(
            $documents,
            (int) ($payload['folders'] ?? 0),
            (int) ($payload['depth'] ?? 0),
            (int) ($payload['templates'] ?? 4),
            (int) ($payload['tmplvars'] ?? 10),
            (int) ($payload['values'] ?? 4),
            (int) ($payload['users'] ?? 0),
            (int) ($payload['member_groups'] ?? 0),
            (int) ($payload['document_groups'] ?? 0),
        ));

        return ['ok' => true, 'batch' => $batch->id, 'rows' => $batch->rows()] + $this->state();
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function drop(array $payload): array
    {
        $wanted = (int) ($payload['batch'] ?? 0);
        $removed = 0;

        foreach ($this->ledger->batches() as $batch) {
            if ($wanted === 0 || $batch->id === $wanted) {
                $removed += $this->generator->drop($batch);
            }
        }

        return ['ok' => true, 'removed' => $removed] + $this->state();
    }
}
