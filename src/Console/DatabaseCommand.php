<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Console;

use hkyss\Fixtures\Generate\Generator;
use hkyss\Fixtures\Generate\Writer;
use hkyss\Fixtures\Record\Ledger;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

abstract class DatabaseCommand extends Command
{
    private ?Ledger $ledger = null;

    protected function connection(): Connection
    {
        $name = $this->option('database') ?: $this->setting('fixtures.connection');

        /** @var DatabaseManager $manager */
        $manager = $this->laravel->make('db');

        return $manager->connection(is_string($name) && $name !== '' ? $name : null);
    }

    protected function ledger(): Ledger
    {
        return $this->ledger ??= new Ledger($this->connection());
    }

    protected function generator(): Generator
    {
        return new Generator(
            $this->connection(),
            $this->ledger(),
            new Writer($this->connection(), max(50, (int) $this->setting('fixtures.chunk', 500)))
        );
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        try {
            return $this->laravel->make('config')->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    protected function reportTarget(): void
    {
        $connection = $this->connection();

        $this->line(sprintf(
            'Writing to <comment>%s</comment>, table prefix <comment>%s</comment>.',
            (string) $connection->getDatabaseName(),
            $connection->getTablePrefix() !== '' ? $connection->getTablePrefix() : '(none)'
        ));
        $this->newLine();
    }
}
