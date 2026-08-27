# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
