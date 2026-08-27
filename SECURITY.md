# Security

## Reporting

Report privately through [GitHub Security Advisories](https://github.com/hkyss/evocms-fixtures/security/advisories/new)
or by email to hkyss.work@protonmail.com. Please don't use a public issue.

Include the version, the recipe you ran, and the output of `php artisan fixture:list --ranges`.
First reply within a week.

## What running fixture:make does

It writes rows — a lot of them — into the database Evolution is configured with, using
Evolution's own credentials. Twenty thousand documents is roughly a hundred thousand rows once
the closure and the values are counted, and the write is not transactional: a batch interrupted
halfway leaves what it had already written, recorded up to that point.

Nothing is overwritten. Every row is new and above the highest id its table already holds, so
no existing content is touched by generating.

## What fixture:drop does

It deletes, permanently, the id ranges on record. That is the only thing it deletes: no
pattern matching, no name prefixes, no "everything above id N".

Losing the record tables loses the ability to remove a batch. There is no recovery command that
identifies generated rows by looking at them, and there will not be one — a heuristic that
guesses what is generated will eventually guess wrong about real content.

## Generated users

`--users` writes rows into `users` and `user_attributes` with a placeholder password that is
not a valid hash, so none of them can sign in. They exist to be joined against, not to be used.

Their email addresses are on `fixtures.invalid`, a domain reserved by RFC 2606 that can never
resolve — so a site that mails its users cannot mail them anywhere real.

Even so: generated users are rows in your users table. On anything reachable from outside,
remove the batch when the test is over rather than leaving them there.

## Before production

Don't. This package exists for staging, local work and benchmarks. It has no safeguard that
recognises a production database, because there is nothing in an Evolution install that
reliably says so — `core/config/app.php` hard-codes its environment to `production` on every
site there is.

The one honest protection is the record: whatever you generate, `fixture:drop` takes back.
