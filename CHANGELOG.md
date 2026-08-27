# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-27

### Added

- `fixture:make`, which generates a document tree with a closure table that agrees with it,
  templates and template variables, their values, and optionally users, member groups and the
  document groups that gate the content.
- `fixture:list` and `fixture:drop`, which read the record of what was generated and remove
  exactly that — by id range, newest batch first, leaving other batches and pre-existing
  content alone.
