<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Mysql;

use hkyss\Fixtures\Generate\Generator;
use hkyss\Fixtures\Generate\Writer;
use hkyss\Fixtures\Plan\Recipe;
use hkyss\Fixtures\Record\Ledger;
use Illuminate\Database\Events\QueryExecuted;
use RuntimeException;

class BatchRecordTest extends DatabaseTestCase
{
    private Ledger $ledger;
    private Generator $generator;
    private bool $busy = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = new Ledger($this->connection);
        $this->generator = new Generator($this->connection, $this->ledger, new Writer($this->connection, 50));
    }

    public function testADroppedBatchLeavesEveryTableAsItWas(): void
    {
        $this->connection->table('site_content')->insert(['id' => 7, 'pagetitle' => 'mine', 'alias' => 'mine']);

        $batch = $this->generator->make($this->recipe());
        $this->generator->drop($batch);

        $this->assertSame([7], $this->ids('site_content'));
        $this->assertSame(0, $this->connection->table('site_content_closure')->count());
        $this->assertSame(0, $this->connection->table('site_tmplvar_contentvalues')->count());
        $this->assertFalse($this->ledger->exists());
    }

    public function testARowAnotherProcessWritesWhileTheBatchRunsSurvivesTheDrop(): void
    {
        $this->whenTableIsWritten('evo_site_tmplvar_contentvalues', function (): void {
            $this->connection->table('site_tmplvar_contentvalues')->insert([
                'tmplvarid' => 999,
                'contentid' => 999,
                'value' => 'foreign',
            ]);
        });

        try {
            $this->generator->make($this->recipe());
        } catch (\Throwable) {
        }

        $this->dropEverything();

        $this->assertSame(['foreign'], $this->connection->table('site_tmplvar_contentvalues')->pluck('value')->all());
        $this->assertSame(0, $this->connection->table('site_content')->count());
    }

    public function testABatchThatDiesPartWayCanStillBeDropped(): void
    {
        $inserts = 0;

        $this->whenTableIsWritten('evo_site_content', function () use (&$inserts): void {
            if (++$inserts === 2) {
                throw new RuntimeException('the process died');
            }
        });

        try {
            $this->generator->make($this->recipe());
            $this->fail('The batch should have died.');
        } catch (RuntimeException) {
        }

        $this->assertNotSame([], $this->ledger->batches());
        $this->assertGreaterThan(0, $this->connection->table('site_content')->count());

        $this->dropEverything();

        $this->assertSame(0, $this->connection->table('site_content')->count());
        $this->assertSame(0, $this->connection->table('site_templates')->count());
        $this->assertSame(0, $this->connection->table('site_tmplvars')->count());
        $this->assertFalse($this->ledger->exists());
    }

    public function testTheLedgerKnowsTheBatchBeforeItsFirstRowIsWritten(): void
    {
        $seen = null;

        $this->whenTableIsWritten('evo_site_templates', function () use (&$seen): void {
            $seen = $this->ledger->batches();
        });

        $this->generator->make($this->recipe());

        $this->assertNotNull($seen);
        $this->assertCount(1, $seen);
        $this->assertNotSame([], $seen[0]->ranges);
    }

    private function recipe(): Recipe
    {
        return new Recipe(documents: 200, folders: 20, templates: 2, tmplvars: 3, valuesPerDocument: 2);
    }

    private function whenTableIsWritten(string $table, callable $then): void
    {
        $this->connection->listen(function (QueryExecuted $query) use ($table, $then): void {
            if ($this->busy || !str_starts_with($query->sql, 'insert into `' . $table . '`')) {
                return;
            }

            $this->busy = true;

            try {
                $then();
            } finally {
                $this->busy = false;
            }
        });
    }

    private function dropEverything(): void
    {
        foreach ($this->ledger->batches() as $batch) {
            $this->generator->drop($batch);
        }
    }

    /** @return list<int> */
    private function ids(string $table): array
    {
        return array_map('intval', $this->connection->table($table)->orderBy('id')->pluck('id')->all());
    }
}
