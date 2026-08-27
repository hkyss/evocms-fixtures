# evocms-fixtures

[![Tests](https://github.com/hkyss/evocms-fixtures/actions/workflows/tests.yml/badge.svg)](https://github.com/hkyss/evocms-fixtures/actions/workflows/tests.yml)
[![Latest version](https://img.shields.io/packagist/v/hkyss/evocms-fixtures.svg)](https://packagist.org/packages/hkyss/evocms-fixtures)
[![PHP](https://img.shields.io/packagist/dependency-v/hkyss/evocms-fixtures/php.svg)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Generates Evolution CMS CE 3 content at scale — a document tree with a closure table that
agrees with it, template variables and their values, users and the permissions that gate the
content — and removes exactly what it made.

```bash
php artisan fixture:make --documents=20000 --values=6
php artisan fixture:list
php artisan fixture:drop
```

## Why

You cannot tell whether a site is slow, an import is correct, or a listing paginates properly
on a database with four documents in it. Filling one by hand is tedious; filling one with a
script is easy until the day you need the site back the way it was.

So the generating is the easy half of this package and the smaller one. The half that earns it
is that every batch is recorded, and `fixture:drop` puts the database back on the row count it
started with — which the test suite asserts, table by table, on every run.

## Install

```bash
cd core
php artisan package:installrequire hkyss/evocms-fixtures "^1.0"
php artisan fixture:make --documents=1000
```

No migrations. The package writes nothing until you ask it to, and the two tables it keeps its
record in are created then and dropped again when the last batch is gone.

## What a batch is

One `fixture:make` writes across up to twelve tables and records the id range it owns in each:

| | |
|---|---|
| `site_templates`, `site_tmplvars`, `site_tmplvar_templates` | the elements the documents point at |
| `site_content`, `site_content_closure` | the tree, and the closure that agrees with it |
| `site_tmplvar_contentvalues` | one value per variable per document |
| `users`, `user_attributes` | web users, when asked for |
| `membergroup_names`, `member_groups` | who they are |
| `documentgroup_names`, `document_groups`, `membergroup_access` | and what they may read |

Every row is written above the highest id its table already holds. That is what makes the
range unambiguous: nothing that existed before the batch can fall inside it, and anything
written after it lands above.

```bash
php artisan fixture:make \
  --documents=20000 --folders=400 --templates=8 --tvs=20 --values=6 \
  --users=500 --member-groups=10 --document-groups=5
```

Defaults live in the config, and any option not given falls back to them.

## The tree

Documents are not a flat list. The first `--folders` of them open as containers, and every
document after that is placed under one of the containers opened so far — spread by a prime
step, so the shape is neither a chain nor a fan, and is the same on every run with the same
numbers.

The closure table is written from that shape, in the convention Evolution uses: a row for the
node itself at depth 0 and one for each ancestor above it, with no row for the virtual root.
A subtree query over generated content behaves the way it will over real content, which is the
whole point of generating it.

## Removing

```bash
php artisan fixture:list --ranges
php artisan fixture:drop --dry-run
php artisan fixture:drop 3
php artisan fixture:drop --all
```

`fixture:drop` deletes by recorded range and by nothing else. A batch removed does not touch a
batch that stayed, and content that was there before the package ran is outside every range it
knows about.

When the last batch is gone the record tables go with it.

## What it will not do

**Write anything you did not ask for.** No migrations, no seeding on install, no defaults
applied to your site. `fixture:make` states what it is about to write and asks first.

**Guess at what it owns.** Removal works from a recorded range, not from a naming convention
or a marker column. If the record is gone, the package will not try to identify its rows by
looking at them.

**Produce content that does not hold together.** One value per variable per document, one
self-pair per node in the closure, and no row pointing at anything that is not there. The test
suite checks all of it against a real server.

## Requirements

PHP 8.2+, MySQL or MariaDB, an installed Evolution CMS CE 3.

## License

MIT.
