<?php

declare(strict_types=1);

use hkyss\Fixtures\Generate\Generator;
use hkyss\Fixtures\Generate\Writer;
use hkyss\Fixtures\Plan\Recipe;
use hkyss\Fixtures\Record\Ledger;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../../vendor/autoload.php';

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => getenv('FIXTURES_TEST_HOST') ?: '127.0.0.1',
    'port' => getenv('FIXTURES_TEST_PORT') ?: '3306',
    'database' => getenv('FIXTURES_TEST_DATABASE') ?: 'evolution',
    'username' => getenv('FIXTURES_TEST_USERNAME') ?: 'root',
    'password' => getenv('FIXTURES_TEST_PASSWORD') ?: 'root',
    'charset' => 'utf8mb4',
    'prefix' => getenv('FIXTURES_TEST_PREFIX') ?: 'evo_',
]);

$connection = $capsule->getConnection();
$ledger = new Ledger($connection);
$generator = new Generator($connection, $ledger, new Writer($connection));
$prefix = $connection->getTablePrefix();

$fail = static function (string $why): never {
    fwrite(STDERR, $why . "\n");

    exit(1);
};

/** @return array<string, int> */
$counts = static function () use ($connection, $prefix): array {
    $counts = [];

    foreach ($connection->select('SHOW TABLES') as $row) {
        $table = (string) array_values((array) $row)[0];

        if (!str_starts_with($table, $prefix) || str_contains($table, 'fixture_')) {
            continue;
        }

        $counts[$table] = (int) $connection->table(substr($table, strlen($prefix)))->count();
    }

    return $counts;
};

$orphans = static function (string $child, string $column, string $parent) use ($connection): int {
    return (int) $connection->table($child . ' as c')
        ->leftJoin($parent . ' as p', 'p.id', '=', 'c.' . $column)
        ->whereNull('p.id')
        ->where('c.' . $column, '<>', 0)
        ->count();
};

$before = $counts();

$first = $generator->make(new Recipe(documents: 400, folders: 30, templates: 3, tmplvars: 8, valuesPerDocument: 3));
$afterFirst = $counts();
$second = $generator->make(new Recipe(documents: 200, folders: 10, templates: 2, tmplvars: 5, valuesPerDocument: 2, users: 40, memberGroups: 4, documentGroups: 3));

printf("batch %d: %d rows, batch %d: %d rows\n", $first->id, $first->rows(), $second->id, $second->rows());

if ($orphans('site_content_closure', 'descendant', 'site_content') !== 0) {
    $fail('The closure points at documents that are not there.');
}

if ($orphans('site_content_closure', 'ancestor', 'site_content') !== 0) {
    $fail('The closure has ancestors that are not documents.');
}

if ($orphans('site_content', 'parent', 'site_content') !== 0) {
    $fail('A document has a parent that is not there.');
}

if ($orphans('site_tmplvar_contentvalues', 'contentid', 'site_content') !== 0) {
    $fail('A template variable value points at a document that is not there.');
}

if ($orphans('site_tmplvar_contentvalues', 'tmplvarid', 'site_tmplvars') !== 0) {
    $fail('A template variable value points at a variable that is not there.');
}

if ($orphans('document_groups', 'document', 'site_content') !== 0) {
    $fail('A document group holds a document that is not there.');
}

if ($orphans('member_groups', 'member', 'users') !== 0) {
    $fail('A member group holds a user that is not there.');
}

$selves = (int) $connection->table('site_content_closure')->whereColumn('ancestor', 'descendant')->count();
$documents = (int) $connection->table('site_content')->count();

if ($selves !== $documents) {
    $fail(sprintf('%d documents but %d self-pairs in the closure.', $documents, $selves));
}

$duplicates = $connection->selectOne(
    sprintf(
        'SELECT COUNT(*) AS `pairs` FROM (SELECT 1 FROM `%s` GROUP BY `tmplvarid`, `contentid` HAVING COUNT(*) > 1) AS `d`',
        $prefix . 'site_tmplvar_contentvalues'
    )
);

if (is_object($duplicates) && ((int) $duplicates->pairs) > 0) {
    $fail('The values carry duplicate (tmplvarid, contentid) pairs; a document holds one value per variable.');
}

echo "Every generated row points at something that exists, and the closure agrees with the tree.\n";

$generator->drop($second);

if ($counts() !== $afterFirst) {
    $fail('Dropping the second batch did not leave the first one alone.');
}

echo "Dropping one batch left the other untouched.\n";

$generator->drop($first);
$after = $counts();

if ($after !== $before) {
    foreach ($before as $table => $rows) {
        if (($after[$table] ?? -1) !== $rows) {
            fwrite(STDERR, sprintf("  %s: %d before, %d after\n", $table, $rows, $after[$table] ?? -1));
        }
    }

    $fail('The database is not where it started.');
}

if ($ledger->exists()) {
    $fail('The ledger outlived the last batch it was keeping.');
}

echo "Both batches removed, every table back to the row count it started with, ledger gone.\n";
