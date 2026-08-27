# Plugin Updates

![Laravel 12.x | 13.x](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-FF2D20?style=flat-square)
![Status](https://img.shields.io/badge/status-private%2Funreleased-lightgrey?style=flat-square)

`qcentic-edge/plugin-updates` gives every first-party package the same answer to one question: has the code moved ahead of the database, and what does it owe?

> [!NOTE]
> First-party Qcentic library (ADR 0005). Unpublished — license (free vs paid) is still an open decision. This is a library, not a panel plugin: it is never installed directly, and nothing here renders.

The model is WordPress'. A package keeps its own version, compares it against the
version of the code that is deployed, and runs its own migrations and seeder. Nothing
central runs a package's schema work, and no package depends on the installer to
finish its own upgrade.

## Features

- `UpdatablePackage::make(...)` — one fluent call per package: name, title, manifest, and optionally migration path, seeder and tables
- `PluginUpdates::packages()` — every package that has declared itself, for anything that reports on them
- `PluginUpdates::ledger()` — the version each package's database is at, in a table the library ensures itself with no migration file
- `PluginUpdates::report()` — the one reading seam: what every registered package owes, right now
- `codeVersion()` — the deployed version via Composer's installed-versions API, correct for path repositories
- No Filament page, resource or navigation item, and no dependency on Filament at all

## Compatibility

| Package | Laravel | PHP |
|---|---|---|
| 0.x (unreleased) | 12.x, 13.x | 8.3+ |

## Installation

Nobody installs this directly. It arrives transitively, as a dependency of the packages
that use it — so a package requires it in its own `composer.json`:

```json
{
    "require": {
        "qcentic-edge/plugin-updates": "*"
    }
}
```

The application still needs to be able to resolve the name. Every first-party package is
consumed as a Composer path repository on this workstation, and a path package's own
requirements are resolved against the application's repositories, not its own — so the
site adds a path repository for the library alongside the ones for its plugins, in
`app/composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/plugin-updates"
        }
    ]
}
```

The library is synced into each site's `packages/` directory like the plugins are. The
repository entry carries no matching `require` line in the site: nothing is installed
until a package declares the dependency, at which point Composer resolves it from that
path.

Laravel's package discovery registers `PluginUpdatesServiceProvider`, so there is nothing
to add to `config/app.php` and no panel wiring of any kind.

## Usage

### Registering a package

One call, from the package's own service provider:

```php
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

PluginUpdates::register(
    UpdatablePackage::make('qcentic-edge/filament-seo')
        ->title('SEO')
        ->manifest(__DIR__.'/../database/updates.php')
        ->migrations(__DIR__.'/../database/migrations')
        ->seeder(SeoSeeder::class)
        ->tables(['seo_meta', 'seo_settings']),
);
```

Package name and manifest are what every package declares; the title defaults to the
package name. Registering without a manifest throws `IncompleteDeclaration` there and
then, naming the package, because the library refuses to guess where a package's
releases are described.

The migration path, the seeder and the tables are optional: a package may own no schema,
no seed data or no tables at all, and the library refuses to guess at any of those
either. Declaring tables is what lets the operator see row counts without the package
writing reporting code.

Registering the same package name twice replaces the declaration rather than listing the
package twice.

### The manifest

One row per release, seeds only:

```php
return [
    '0.4.0' => ['seed' => false],
    '0.5.0' => ['seed' => false],
    '0.6.0' => ['seed' => true],
];
```

Schema work is never declared here. Whether a package owes schema work is read from
Laravel's own `migrations` ledger, diffed against that package's migration path — so
the manifest and the database can never disagree about what has been applied. Writing
the migration file *is* the declaration.

An entry is pending when it is above the stored version and at or below the code version,
and the flags of the pending entries are unioned: if any pending release says
`seed: true`, the seeder is owed once, however many of them asked. A site several
releases behind is the normal condition, not the exceptional one, so reading only the
newest entry would lose data a skipped release meant to add.

Both bounds matter. The lower one keeps a site from re-owing work it has already done.
The upper one means a row added before the `composer.json` bump — the order this
one-row checklist invites — is not reported as owed by a site whose deployed code has
not reached that release and could not run its work anyway. A package whose code version
Composer cannot resolve has nothing to bound against, so every entry above the stored
version is pending.

Releases are ordered by version, not by string, so a manifest listing `0.10.0` before
`0.9.0` — or in no order at all — still reports correctly. A manifest that is missing or
does not return releases throws `UnreadableManifest` rather than reading as empty: a
manifest that quietly read as empty would report a package several releases behind as
owing nothing.

There is no assets flag: assets are overwritten wholesale by the deploy and can never
be owed at runtime.

Old migration files are history. They are never edited and never deleted, because a
database several versions behind can only climb if every historical step is still on
disk in the current release.

### The update report

One call answers the whole question, for every registered package at once:

```php
use QcenticEdge\PluginUpdates\PluginUpdates;

PluginUpdates::report()->all();                             // array<string, PackageStatus>, keyed by package name
PluginUpdates::report()->status('qcentic-edge/filament-seo'); // ?PackageStatus
PluginUpdates::report()->owing();                           // only the packages with work to do
PluginUpdates::report()->anythingOwed();                    // for a badge
```

A `PackageStatus` is a result object, read once and answered in full:

| Member | Type | Meaning |
|---|---|---|
| `$name` | `string` | Composer package name |
| `$title` | `string` | the name the operator sees |
| `$storedVersion` | `?string` | the version this package's database is at, as the ledger recorded it; `null` when it has never recorded one |
| `$codeVersion` | `?string` | the version of the code deployed |
| `$pendingVersions` | `list<string>` | manifest releases above the stored version and at or below the code version, oldest first |
| `$pendingMigrations` | `list<string>` | unapplied migration names in this package's own path, in run order |
| `$seedingVersions` | `list<string>` | the pending releases that asked for a seed |
| `$problem` | `?string` | why this package could not be reported on; `null` when it could |
| `tables()` | `list<TableCount>` | the declared tables, each with `$name` and `$rows` (`null` when the table does not exist yet) |
| `versionsBehind()` | `int` | how many releases the database is catching up |
| `schemaOwed()` | `bool` | `$pendingMigrations !== []` |
| `seedOwed()` | `bool` | `$seedingVersions !== []` |
| `codeVersionKnown()` | `bool` | whether Composer knows the deployed version, so there is one to advance the stored version to |
| `isBroken()` | `bool` | this package's own manifest could not be read; `$problem` says how |
| `owesWork()` | `bool` | broken, schema owed, or seed owed |

The report is the only way to read update state. Nothing else in the system queries it
directly, and nothing reimplements any part of it.

Two things are worth being precise about, because they are the design and not an
implementation detail.

**Schema comes from the migrator, never from the manifest.** `$pendingMigrations` is the
diff of that package's own migration path against Laravel's `migrations` ledger. That
ledger is per-file, so the answer is exact, already incremental over any size of gap, and
cannot disagree with the database. It follows that a site that has been running for years
and has never recorded a stored version still reports no schema work: its migrations are
applied, and that is the only thing consulted. It also follows that a migration file no
release ever mentioned still surfaces — undeclared schema work is never skipped.

**A version gap is not, on its own, an obligation.** A package whose path is fully applied
and whose pending releases all decline a seed owes nothing, however many releases behind
it is. `versionsBehind()` is what the operator is told to expect; `owesWork()` is what
decides whether there is a button.

Two more are worth stating for anything that renders the report.

**Row counts are counted only when asked.** `tables()` runs its counts on the first call
and remembers them for the life of that status object; building a status counts nothing.
A badge on every page of the panel asks `anythingOwed()` and must not pay for a count
sweep of every declared table of every registered package to get a yes or a no.

**A package whose manifest cannot be read is broken, not quiet.** The failure belongs to
that package alone: `isBroken()` is true, `$problem` carries the message naming the file
and what is wrong with it, `owesWork()` is true so the package is never mistaken for an
up-to-date one, and every other package still reports normally beside it. It reports no
pending versions, no pending migrations and no seeds, because what it owes is exactly
what could not be read. A renderer should show it as needing attention rather than as
work to run.

### The version ledger

```php
PluginUpdates::ledger()->record('qcentic-edge/filament-seo', '0.6.0');
PluginUpdates::ledger()->storedVersion('qcentic-edge/filament-seo'); // '0.6.0'
```

A package the ledger has never heard of reads back as `null`, never as an assumed
version — and so does every package on a database that has never seen the ledger table.

The ledger's table, `plugin_update_versions`, ships as no migration file of its own
and cannot: it has to exist before the machinery that runs migrations can report
anything, and on a stateless edge host there is no shell to create it with. It is
ensured idempotently behind a table-existence check on the first write, which behaves
identically whether the installer is present, absent, or added later.

Reading never creates it. Several replicas serve the panel and one of them may be on a
read-only connection, where DDL on a read path would throw where a null will do.

### Code version

The deployed version comes from Composer's installed-versions API, which resolves
path repositories correctly — a path package reports the version its own
`composer.json` declares.

```php
PluginUpdates::package('qcentic-edge/filament-seo')->codeVersion();
```

## Testing

```bash
composer install
composer test
```

Pest 4 + Orchestra Testbench, self-contained in this repo (no app needed). The library
depends on nothing but the framework, so the test app boots with nothing but the
framework and the library's own provider.

## Changelog

No tagged releases yet.

## Contributing

Private first-party library — internal contributors only, for now.

## License

Unpublished. All rights reserved until the free-vs-paid decision lands (ADR 0005).
