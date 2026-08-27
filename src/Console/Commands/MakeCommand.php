<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Console\Commands;

use hkyss\Fixtures\Console\DatabaseCommand;
use hkyss\Fixtures\Plan\Recipe;
use InvalidArgumentException;

class MakeCommand extends DatabaseCommand
{
    protected $signature = 'fixture:make
        {--documents= : How many documents to generate}
        {--folders= : How many of them open as containers}
        {--depth= : Cap the tree at this many levels; 0 lets it find its own}
        {--templates= : How many templates to generate}
        {--tvs= : How many template variables to generate}
        {--values= : Template variable values per document}
        {--users= : How many web users to generate}
        {--member-groups= : How many member groups to put them in}
        {--document-groups= : How many document groups to gate the content with}
        {--database= : Connection to write to}
        {--force : Skip the confirmation}';

    protected $description = 'Generate a batch of Evolution content, and record what it was';

    public function handle(): int
    {
        try {
            $recipe = $this->recipe();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->reportTarget();
        $this->line(sprintf('About to write <options=bold>%s</>.', $recipe->describe()));
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Generate?', false)) {
            $this->line('Nothing was written.');

            return self::SUCCESS;
        }

        $batch = $this->generator()->make($recipe, function (string $what, int $rows): void {
            $this->line(sprintf('  <fg=green>%8d</> %s', $rows, $what));
        });

        $this->newLine();
        $this->line(sprintf('<options=bold>Batch %d — %d rows.</>', $batch->id, $batch->rows()));
        $this->line(sprintf('Take it back out with <comment>php artisan fixture:drop %d</comment>.', $batch->id));

        return self::SUCCESS;
    }

    private function recipe(): Recipe
    {
        return new Recipe(
            $this->number('documents', 'fixtures.defaults.documents', 1000),
            $this->number('folders', 'fixtures.defaults.folders', 0),
            $this->number('depth', 'fixtures.defaults.max_depth', 0),
            $this->number('templates', 'fixtures.defaults.templates', 4),
            $this->number('tvs', 'fixtures.defaults.tmplvars', 10),
            $this->number('values', 'fixtures.defaults.values_per_document', 4),
            $this->number('users', 'fixtures.defaults.users', 0),
            $this->number('member-groups', 'fixtures.defaults.member_groups', 0),
            $this->number('document-groups', 'fixtures.defaults.document_groups', 0),
        );
    }

    private function number(string $option, string $key, int $fallback): int
    {
        $given = $this->option($option);

        if ($given !== null && $given !== '') {
            return (int) $given;
        }

        return (int) $this->setting($key, $fallback);
    }
}
