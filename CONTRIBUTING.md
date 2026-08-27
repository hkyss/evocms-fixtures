# Contributing

## What this package may do

Write rows into a database that belongs to someone else, in quantity. Two rules follow, and
they bound every change made here:

1. **Own what you wrote, and nothing else.** Every generated row goes above the highest id its
   table already holds, and the range is recorded before the batch is called done. Removal
   works from that record. A change that makes rows identifiable by shape rather than by
   record — a marker column, a name prefix, a magic id offset — is a change that will one day
   delete somebody's content.
2. **Generate what the CMS would have generated.** A closure table that disagrees with the
   tree, or two values for one variable on one document, makes a database that benchmarks and
   tests will lie about. So does a tree one level deep: it passes every referential check and
   still measures nothing, because no query over it ever descends. The round trip checks the
   first two and `TreeTest` the third; keep all three checking.

## Comments

Do not write comments. Names carry the meaning, and prose that restates the code rots the
moment the code moves. A docblock that only repeats a signature is a comment too.

Where a comment is genuinely unavoidable — a constraint the reader cannot see, a convention
that lives in another project — write one sentence. Not two.

## Language

Everything written is English: identifiers, commit messages, test names, READMEs and the one
sentence a comment is allowed to be.

## The two layers

Everything that decides *what* to generate is pure. `Recipe` validates the numbers, `Tree`
turns them into a shape, and neither touches a database — so both are tested without one.
Everything that writes sits behind `Writer` and `Generator`. Keep the seam there.

`Tree` works in index space, 1..N. Mapping an index to a real id is the generator's job, and
that is what lets the shape be tested without reserving anything.

## Running the checks

```bash
composer install
composer check
```

`check` is php-cs-fixer, phpstan at level 6 and phpunit, and CI runs all three on PHP 8.2
through 8.4 and on the lowest Illuminate the package claims to support.

## The round trip

`tests/Schema/roundtrip.php` loads a real Evolution schema, generates two batches, and then
asserts four things: every generated row points at something that exists, the closure has one
self-pair per document and no orphans, dropping one batch leaves the other untouched, and
dropping both puts every table back on the row count it started with.

```bash
mysql -h 127.0.0.1 -uroot -proot -e 'CREATE DATABASE evolution'
mysql -h 127.0.0.1 -uroot -proot evolution < tests/Schema/baseline.sql
php tests/Schema/roundtrip.php
```

`FIXTURES_TEST_HOST`, `FIXTURES_TEST_PORT`, `FIXTURES_TEST_DATABASE`, `FIXTURES_TEST_USERNAME`,
`FIXTURES_TEST_PASSWORD` and `FIXTURES_TEST_PREFIX` override the defaults.

`tests/Schema/baseline.sql` is the structure of one Evolution CE 3.1 installation, not a
canonical artifact of the project. Regenerate it with:

```bash
mysqldump -uroot -proot --no-data --skip-add-drop-table --skip-comments --compact <database> \
  | sed 's/ AUTO_INCREMENT=[0-9]*//' > tests/Schema/baseline.sql
```

## Adding to a batch

A new table in a batch needs three things: a way to reserve its range, rows that point only at
rows the same batch made, and a place in the removal order that is the reverse of the writing
order. If the table has no single-column key, record the column the range is keyed on —
`site_tmplvar_templates` is the example already in there.

## Commits

Imperative and lower-case after the type: `feat:`, `fix:`, `docs:`, `ci:`, `chore:`. The body
carries the reasoning when the subject cannot.
