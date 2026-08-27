# Plugin Updates

![Laravel 12.x | 13.x](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-FF2D20?style=flat-square)
![Status](https://img.shields.io/badge/status-private%2Funreleased-lightgrey?style=flat-square)

`qcentic-edge/plugin-updates` gives every first-party package the same answer to one question: has the code moved ahead of the database, and what does it owe?

> [!NOTE]
> First-party Qcentic library (ADR 0005). Unpublished — license (free vs paid) is still an open decision. This is a library, not a panel plugin: it drops the `filament-` prefix, ships no page, resource or navigation item, and is never installed directly — it arrives transitively. It does require `filament/filament`, because it renders one notice into the panel's topbar and needs Filament's render hooks to do it; that dependency is not what makes a package a panel plugin here.

The model is WordPress'. A package keeps its own version, compares it against the
version of the code that is deployed, and runs its own migrations and seeder. Nothing
central runs a package's schema work, and no package depends on the installer to
finish its own upgrade.

## Features

- `UpdatablePackage::make(...)` — one fluent call per package: name, title, manifest, and optionally migration path, seeder and tables
- `PluginUpdates::packages()` — every package that has declared itself, for anything that reports on them
- `PluginUpdates::ledger()` — the version each package's database is at, in a table the library ensures itself with no migration file
- `PluginUpdates::report()` — the one reading seam: what every registered package owes, right now
- `PluginUpdates::run(...)` — one package catches up: its unapplied migrations, its seeder if a pending release owes one, then its stored version. Never on boot
- `codeVersion()` — the deployed version via Composer's installed-versions API, correct for path repositories
- A topbar notice, so a package on a site with no installer still tells the operator its database is behind — suppressed where the installer's Updates page is present, and skipped entirely in a host with no Livewire to render it
- No Filament page, resource or navigation item

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

Registering when the library's own service provider is not registered — a Composer install
that skipped `package:discover`, or a host that lists its providers by hand — throws
`UnreachableRegistry`, naming the provider and how to get it back. Without that check the
call would appear to succeed: the registry is a concrete class, so the container would
auto-wire one with no binding behind it, the declaration would land in an object discarded
at the end of the call, and every package would then report its database as up to date.
A stale database read as healthy is the failure this library exists to prevent, so it is
refused where the mistake is rather than discovered by an operator later. Reading the
registry is refused the same way, because an empty answer is exactly the dangerous one.

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
| `$seederDeclared` | `bool` | whether the package declared a seeder for a pending release to use |
| `isBroken()` | `bool` | this package's own manifest could not be read; `$problem` says how |
| `owesWork()` | `bool` | broken, schema owed, or seed owed |
| `runnable()` | `bool` | whether `run()` would go ahead rather than refuse before touching anything |
| `unrunnableReason()` | `?string` | why it would refuse, naming what is missing; `null` when it would go ahead |
| `needsAttention()` | `bool` | owes work and cannot run it — the third state, and the one with no button |

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

**Owing work and being able to do it are different questions.** `owesWork()` says there is
something to do; `runnable()` says a click would do it rather than be refused. A renderer
that asked only the first draws a button whose only effect is an exception, and a renderer
that answered the second for itself — by asking the registry what the package declared —
would be a second view of update state beside this one. So the report answers both, the
runner refuses on exactly the same answer, and `unrunnableReason()` is the sentence both
the operator and the exception are given. `needsAttention()` names the state a renderer
actually branches on: owes work, has nothing runnable, show the reason instead of a button.

**A package whose manifest cannot be read is broken, not quiet.** The failure belongs to
that package alone: `isBroken()` is true, `$problem` carries the message naming the file
and what is wrong with it, `owesWork()` is true so the package is never mistaken for an
up-to-date one, and every other package still reports normally beside it. It reports no
pending versions, no pending migrations and no seeds, because what it owes is exactly
what could not be read. A renderer should show it as needing attention rather than as
work to run.

### Running an update

```php
PluginUpdates::run('qcentic-edge/filament-seo');

// what it owes now — read back the same way anything else reads it
PluginUpdates::report()->status('qcentic-edge/filament-seo')->owesWork(); // false
```

One package at a time, always from an explicit action, and three steps in order: the
unapplied migrations in that package's own path, then its seeder if any pending release
owes one, then the stored version advanced to the code version.

`run()` returns nothing on purpose. What the package owes afterwards is read back through
`report()`, so a caller never holds a private answer alongside the one everything else
reads.

**Any size of gap is the same call.** An operator who skipped five releases and one who is
a single release behind get the same single action. `Migrator::run()` over one directory
is the files in that directory minus the ones the `migrations` ledger already records, so
a path-scoped run is exactly incremental over an arbitrary gap with no bookkeeping of its
own — and reaches no other package's files. There is no catch-up mode to get wrong,
because there is no catch-up mode: the multi-version path and the single-version path are
literally the same code.

**Failure is a retry, and a partial run is still progress.** The migration ledger records
each file as it succeeds, so a run that dies halfway through a four-release gap keeps
everything applied up to that point. Nothing wraps the batch in a transaction, and nothing
may: a rollback of the whole batch is what would make a host with a request timeout unable
to finish a long catch-up however many times an operator retried. The stored version
advances only after every step succeeded, so a package interrupted partway still reports
as behind, the button stays, and the next click resumes from the first unapplied file
rather than starting over.

**The stored version advances to the code version**, not to the newest pending release, so
an operator is never left stranded on an intermediate version.

**The seeder runs once**, however many pending releases asked for it — which is why the
one a package declares must be idempotent.

A run refuses, before it has touched anything, when the library would otherwise have to
guess. Each throws `UnrunnablePackage` naming the package and what to declare — and each
except the first is `PackageStatus::unrunnableReason()`, so what a renderer shows an
operator and what a run refuses on are the same words from the same place:

| Refusal | Why |
|---|---|
| the package never registered | there is nothing to run |
| `isBroken()` — its manifest could not be read | whether a seed is owed is unknown, and running blind is what the design forbids |
| `codeVersionKnown()` is false | there is no version to advance the database to, and recording an invented one would report a stale database as current for ever after |
| a pending release owes a seed and no seeder was declared | skipping it quietly would lose the data that release meant to add |

**Nothing runs on boot.** Registering a package does no work of any kind; the only thing
that ever touches the schema is this call. Several replicas starting the same schema
change on deploy is the failure that avoids.

### The topbar notice

On a site with no installer plugin, a package still has to be able to tell an operator
that its database is behind — otherwise a redeploy quietly leaves a stale schema running.
The library registers one render hook on the panel's topbar, so the notice is visible from
every page rather than from a screen an operator has to go and find. There is nothing to
wire up: package discovery registers the library's provider, and the provider registers
the hook.

**A host with no Livewire gets no notice, and no complaint.** The notice is a Livewire
component drawn into a Filament panel, so the provider registers it only where Livewire is
actually there to draw it — installed *and* its provider registered, which is one question
to the container. Most packages take this library for its reporting alone, and their own
test applications are bare Laravel apps with no panel in them; registering a notice such an
app cannot draw would force every one of them to boot Filament and Livewire, in a
particular order, for a surface they will never show. A package is not made to boot a panel
to declare that its database is behind.

The skip is a skip precisely because the notice is optional. A package that cannot render
one still reports correctly to everything that asks. That is the opposite of a package that
cannot reach the registry, which reports a stale database as healthy — so that one throws,
and the two conditions are answered separately and never through each other.

Each package that owes work gets its own badge, naming itself, with the action that brings
it up to date beside it. A package that owes nothing renders nothing at all, and a panel
where every package is level renders no notice — the topbar is quiet when it should be.
A package that owes work the library would refuse to run gets no button: it is badged as
needing attention and carries `unrunnableReason()` instead, because a click there would
only ever produce the refusal.

The notice is a renderer and nothing else. Every question it asks goes to
`PluginUpdates::report()`, and it reimplements no part of it.

**With the installer present, the notice suppresses itself**, leaving the installer's
Updates page as the one place update state is shown. Presence is detected by asking the
current panel whether a plugin is registered under the id `installer` — a string, resolved
at runtime, against Filament's own API. Nothing here imports an installer class, and the
library must never require `qcentic-edge/filament-installer`: the dependency arrow points
from packages to this library and never to the installer. Asking the panel is also the
more accurate question than asking Composer, because the installer's install flow is plain
routes: a site can have the package installed without registering its panel plugin, and
then there is no Updates page and this notice is the only surface there is.

**Who sees it** matches the installer's Updates page rather than inventing a second rule:
super admin where the user model exposes roles, any authenticated panel user on an app with
no role package. The rule is `Access\Operator`, not a method on the notice, because it is
not a rendering concern and not the notice's alone — anything else that renders update
state reads it from there rather than writing its own. The action re-checks before it runs,
so the gate is not only a rendering decision.

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

Pest 4 + Orchestra Testbench, self-contained in this repo (no app needed). The default test
app is a panel, because the notice renders into one: Filament's providers, Livewire's, one
panel provider and the library's own. They are listed rather than discovered, and listed in
the order Composer's package discovery would produce — Filament's support provider rebinds a
Livewire mechanism, and rebinding one Livewire has already registered leaves that mechanism
stateless.

The `Host` suite boots two other shapes of application, because the library has to hold in
both: one with the library's provider and nothing else, which is what a consuming package's
own test app looks like, and one where the library's provider never registered at all.

## Changelog

No tagged releases yet.

## Contributing

Private first-party library — internal contributors only, for now.

## License

Unpublished. All rights reserved until the free-vs-paid decision lands (ADR 0005).
