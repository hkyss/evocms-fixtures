<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Generate;

use Closure;
use hkyss\Fixtures\Plan\Recipe;
use hkyss\Fixtures\Plan\Tree;
use hkyss\Fixtures\Record\Batch;
use hkyss\Fixtures\Record\Ledger;
use hkyss\Fixtures\Record\Range;
use Illuminate\Database\Connection;

class Generator
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Ledger $ledger,
        private readonly Writer $writer,
    ) {
    }

    public function make(Recipe $recipe, ?Closure $progress = null): Batch
    {
        $announce = $progress ?? static function (): void {
        };
        $batch = $this->ledger->open($recipe);
        $ranges = [];

        $templates = $this->reserve('site_templates');
        $ranges[] = $this->note($batch, 'site_templates', 'id', $templates, $this->writer->into(
            'site_templates',
            $this->templates($recipe, $templates)
        ));
        $announce('templates', $recipe->templates);

        $tmplvars = $this->reserve('site_tmplvars');

        if ($recipe->tmplvars > 0) {
            $ranges[] = $this->note($batch, 'site_tmplvars', 'id', $tmplvars, $this->writer->into(
                'site_tmplvars',
                $this->tmplvars($recipe, $tmplvars)
            ));
            $ranges[] = $this->note($batch, 'site_tmplvar_templates', 'tmplvarid', $tmplvars, $this->writer->into(
                'site_tmplvar_templates',
                $this->links($recipe, $tmplvars, $templates)
            ));
            $announce('template variables', $recipe->tmplvars);
        }

        $tree = Tree::of($recipe->documents, $recipe->foldersOrDefault(), $recipe->maxDepth);
        $documents = $this->reserve('site_content');
        $ranges[] = $this->note($batch, 'site_content', 'id', $documents, $this->writer->into(
            'site_content',
            $this->documents($recipe, $tree, $documents, $templates)
        ));
        $announce('documents', $recipe->documents);

        $closure = $this->reserve('site_content_closure', 'closure_id');
        $ranges[] = $this->note($batch, 'site_content_closure', 'closure_id', $closure, $this->writer->into(
            'site_content_closure',
            $this->closure($tree, $documents)
        ));
        $announce(sprintf('closure rows, %d level(s) deep', $tree->deepest() + 1), $tree->closureRows());

        if ($recipe->valuesPerDocument > 0) {
            $values = $this->reserve('site_tmplvar_contentvalues');
            $ranges[] = $this->note($batch, 'site_tmplvar_contentvalues', 'id', $values, $this->writer->into(
                'site_tmplvar_contentvalues',
                $this->values($recipe, $documents, $tmplvars)
            ));
            $announce('template variable values', $recipe->values());
        }

        foreach ($this->people($recipe, $batch, $documents, $announce) as $range) {
            $ranges[] = $range;
        }

        return new Batch($batch, $recipe, array_values(array_filter(
            $ranges,
            static fn (Range $range): bool => !$range->isEmpty()
        )), time());
    }

    public function drop(Batch $batch, ?Closure $progress = null): int
    {
        $announce = $progress ?? static function (): void {
        };
        $removed = 0;

        foreach (array_reverse($batch->ranges) as $range) {
            $gone = 0;

            do {
                $deleted = $this->connection->table($range->table)
                    ->whereBetween($range->keyColumn, [$range->first, $range->last])
                    ->limit(2000)
                    ->delete();
                $gone += $deleted;
            } while ($deleted > 0);

            $announce($range->table, $gone);
            $removed += $gone;
        }

        $this->ledger->close($batch->id);

        return $removed;
    }

    /** @return list<Range> */
    private function people(Recipe $recipe, int $batch, int $documents, Closure $announce): array
    {
        if ($recipe->users < 1) {
            return [];
        }

        $ranges = [];
        $users = $this->reserve('users');
        $ranges[] = $this->note($batch, 'users', 'id', $users, $this->writer->into(
            'users',
            $this->rows($recipe->users, static fn (int $n): array => [
                'id' => $users + $n,
                'username' => sprintf('fixture-user-%d', $users + $n),
                'password' => str_repeat('x', 32),
            ])
        ));

        $attributes = $this->reserve('user_attributes');
        $ranges[] = $this->note($batch, 'user_attributes', 'id', $attributes, $this->writer->into(
            'user_attributes',
            $this->rows($recipe->users, static fn (int $n): array => [
                'id' => $attributes + $n,
                'internalKey' => $users + $n,
                'fullname' => sprintf('Fixture user %d', $n + 1),
                'email' => sprintf('user-%d@fixtures.invalid', $users + $n),
            ])
        ));
        $announce('users', $recipe->users);

        if ($recipe->memberGroups < 1) {
            return $ranges;
        }

        $groups = $this->reserve('membergroup_names');
        $ranges[] = $this->note($batch, 'membergroup_names', 'id', $groups, $this->writer->into(
            'membergroup_names',
            $this->rows($recipe->memberGroups, static fn (int $n): array => [
                'id' => $groups + $n,
                'name' => sprintf('fixture-group-%d', $groups + $n),
            ])
        ));

        $members = $this->reserve('member_groups');
        $ranges[] = $this->note($batch, 'member_groups', 'id', $members, $this->writer->into(
            'member_groups',
            $this->rows($recipe->users, static fn (int $n): array => [
                'id' => $members + $n,
                'user_group' => $groups + ($n % $recipe->memberGroups),
                'member' => $users + $n,
            ])
        ));
        $announce('member groups', $recipe->memberGroups);

        if ($recipe->documentGroups < 1) {
            return $ranges;
        }

        $docGroups = $this->reserve('documentgroup_names');
        $ranges[] = $this->note($batch, 'documentgroup_names', 'id', $docGroups, $this->writer->into(
            'documentgroup_names',
            $this->rows($recipe->documentGroups, static fn (int $n): array => [
                'id' => $docGroups + $n,
                'name' => sprintf('fixture-docgroup-%d', $docGroups + $n),
            ])
        ));

        $assignments = $this->reserve('document_groups');
        $ranges[] = $this->note($batch, 'document_groups', 'id', $assignments, $this->writer->into(
            'document_groups',
            $this->rows($recipe->documents, static fn (int $n): array => [
                'id' => $assignments + $n,
                'document_group' => $docGroups + ($n % $recipe->documentGroups),
                'document' => $documents + $n,
            ])
        ));

        $access = $this->reserve('membergroup_access');
        $ranges[] = $this->note($batch, 'membergroup_access', 'id', $access, $this->writer->into(
            'membergroup_access',
            $this->rows($recipe->memberGroups, static fn (int $n): array => [
                'id' => $access + $n,
                'membergroup' => $groups + $n,
                'documentgroup' => $docGroups + ($n % $recipe->documentGroups),
            ])
        ));
        $announce('document groups', $recipe->documentGroups);

        return $ranges;
    }

    /** @return iterable<array<string, mixed>> */
    private function templates(Recipe $recipe, int $base): iterable
    {
        return $this->rows($recipe->templates, static fn (int $n): array => [
            'id' => $base + $n,
            'templatename' => sprintf('Fixture template %d', $n + 1),
            'templatealias' => sprintf('fixture-template-%d', $base + $n),
            'description' => 'Generated by evocms-fixtures',
            'content' => '[*content*]',
        ]);
    }

    /** @return iterable<array<string, mixed>> */
    private function tmplvars(Recipe $recipe, int $base): iterable
    {
        return $this->rows($recipe->tmplvars, static fn (int $n): array => [
            'id' => $base + $n,
            'type' => 'text',
            'name' => sprintf('fixture_tv_%d', $base + $n),
            'caption' => sprintf('Fixture TV %d', $n + 1),
            'description' => 'Generated by evocms-fixtures',
            'rank' => $n,
        ]);
    }

    /** @return iterable<array<string, mixed>> */
    private function links(Recipe $recipe, int $tmplvars, int $templates): iterable
    {
        for ($v = 0; $v < $recipe->tmplvars; $v++) {
            for ($t = 0; $t < $recipe->templates; $t++) {
                yield ['tmplvarid' => $tmplvars + $v, 'templateid' => $templates + $t, 'rank' => $v];
            }
        }
    }

    /** @return iterable<array<string, mixed>> */
    private function documents(Recipe $recipe, Tree $tree, int $base, int $templates): iterable
    {
        $body = str_repeat('lorem ipsum dolor sit amet ', 40);

        for ($node = 1; $node <= $tree->size(); $node++) {
            $parent = $tree->parentOf($node);

            yield [
                'id' => $base + $node - 1,
                'pagetitle' => sprintf('Fixture document %d', $node),
                'alias' => sprintf('fixture-%d', $base + $node - 1),
                'parent' => $parent === 0 ? 0 : $base + $parent - 1,
                'isfolder' => $tree->isFolder($node) ? 1 : 0,
                'template' => $templates + ($node % $recipe->templates),
                'menuindex' => $node % 100,
                'published' => 1,
                'deleted' => 0,
                'searchable' => 1,
                'cacheable' => 1,
                'content' => $body,
            ];
        }
    }

    /** @return iterable<array<string, mixed>> */
    private function closure(Tree $tree, int $base): iterable
    {
        foreach ($tree->closure() as [$ancestor, $descendant, $depth]) {
            yield [
                'ancestor' => $base + $ancestor - 1,
                'descendant' => $base + $descendant - 1,
                'depth' => $depth,
            ];
        }
    }

    /** @return iterable<array<string, mixed>> */
    private function values(Recipe $recipe, int $documents, int $tmplvars): iterable
    {
        $filler = str_repeat('word ', 12);

        for ($node = 0; $node < $recipe->documents; $node++) {
            for ($n = 0; $n < $recipe->valuesPerDocument; $n++) {
                yield [
                    'tmplvarid' => $tmplvars + (($node + $n) % $recipe->tmplvars),
                    'contentid' => $documents + $node,
                    'value' => sprintf('value %d-%d %s', $node, $n, $filler),
                ];
            }
        }
    }

    /** @return iterable<array<string, mixed>> */
    private function rows(int $count, Closure $shape): iterable
    {
        for ($n = 0; $n < $count; $n++) {
            yield $shape($n);
        }
    }

    private function note(int $batch, string $table, string $column, int $first, int $rows): Range
    {
        $last = (int) $this->connection->table($table)->max($column);
        $range = new Range($table, $column, $first, max($first, $last), $rows);
        $this->ledger->note($batch, $range);

        return $range;
    }

    private function reserve(string $table, string $column = 'id'): int
    {
        return ((int) $this->connection->table($table)->max($column)) + 1;
    }
}
