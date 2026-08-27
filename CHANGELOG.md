# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.2] - 2026-08-27

### Changed

- `fixture:make` no longer opens with two lines promising that every row lands above what the
  tables already hold and that the batch is recorded so it can be dropped again. The first is
  what the package is for, and the second is printed as a runnable command once the batch
  exists. What is left is where it writes, what it is about to write, and the question.

## [1.2.1] - 2026-08-27

### Changed

- The panel says less. The form's note repeated the limit the endpoint states when it refuses
  a batch, and promised the writes could be dropped again beside the tab that drops them; the
  benchmark explained itself in a placeholder and called itself read-only under its results.
  All of it is in the README. The ceiling moved into the Documents label, which is read before
  the number is typed rather than after it is refused.

## [1.2.0] - 2026-08-27

### Fixed

- The generated tree was one level deep whatever it was asked for. Containers were taken from
  the front of the range, so they hung off the root and everything else sat directly under
  them; and because a container landed on an exact multiple of the step while the pool was as
  large as the containers opened so far, the arithmetic picking its parent came out zero every
  time, which is the root. Containers are now spread across the range and the parent is picked
  from a sequence that does not share a factor with the step.

### Added

- `--depth` caps the number of levels; zero, the default, lets the tree find its own.

## [1.1.0] - 2026-08-27

### Added

- A panel: a pill on the site that lists batches, generates one, drops one and runs a
  read-only benchmark over the queries Evolution leans on. Off unless `FIXTURES_PANEL=gated`,
  drawn only for a signed-in manager, and every request it makes carries a token derived from
  that session and the site id. `true` is refused — the endpoint writes and deletes rows.
- `Integration\Evolution\FixturesEvolutionServiceProvider`, which carries the panel and the
  console commands both.

## [1.0.0] - 2026-08-27

### Added

- `fixture:make`, which generates a document tree with a closure table that agrees with it,
  templates and template variables, their values, and optionally users, member groups and the
  document groups that gate the content.
- `fixture:list` and `fixture:drop`, which read the record of what was generated and remove
  exactly that — by id range, newest batch first, leaving other batches and pre-existing
  content alone.
