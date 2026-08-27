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

## The panel

`FIXTURES_PANEL=gated` puts an endpoint on the front end of the site that generates and deletes
content. Everything below is the reason it takes three things to reach it.

**It is off by default, and only `gated` turns it on.** `true`, `1`, `on` and anything else are
refused, including in a development environment. There is no value that means "on for
everyone", because there is no safe one.

**A gate decides who sees it.** The Evolution integration supplies a signed-in manager session;
`fixtures.panel.gate` replaces that with your own callable, which must return exactly `true`.
Without a gate, `gated` stays shut.

**Every request carries a token** derived from the session id and the site id with HMAC-SHA256,
compared with `hash_equals`. A request without the token is refused with 403 before anything is
read or written, so a page on another origin cannot use a manager's open session to generate
content. The token never leaves the server except inside the panel's own markup, which only a
gated viewer receives.

**The endpoint answers four actions and nothing else**: `state`, `make`, `drop`, `bench`. An
action it does not recognise is refused without touching the database, and the benchmark issues
`SELECT` only.

**A batch from the panel is capped** at `FIXTURES_PANEL_MAX` documents, 20000 by default. Not
for safety alone: a browser request that writes a million rows times out halfway, and a batch
recorded halfway is worse than one refused outright.

Leave the panel off on anything reachable from outside. It is a tool for a bench and a staging
box, and the console commands do everything it does.

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
