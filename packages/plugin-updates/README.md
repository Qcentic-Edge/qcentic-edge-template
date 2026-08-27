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
- `PluginUpdates::report()` — the one reading seam: what every registered package owes, right now, including where its migrations and seeder are
- `PluginUpdates::run(...)` — one package catches up: its unapplied migrations, its seeder if a pending release owes one, then its stored version. Never on boot
- `codeVersion()` — the deployed version via Composer's installed-versions API, correct for path repositories
- A topbar notice, so a package on a site with no installer still tells the operator its database is behind — suppressed where the installer's Updates page is present, and skipped entirely in a host without both Livewire and a Filament panel to render it in
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
to add to `config/app.php` and no panel wiring of any kind. That provider extends Spatie's
`PackageServiceProvider`, like every other first-party package here — it ships the two
Blade views the topbar notice renders, and `->hasViews()` is what that base class is for.
It ships no config, no translations and no migrations of its own.

## Usage

### Registering a package

One call, from the package's own service provider — in `packageBooted()`, never in
`packageRegistered()`:

```php
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

public function packageBooted(): void
{
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/filament-seo')
            ->title('SEO')
            ->manifest(__DIR__.'/../database/updates.php')
            ->migrations(__DIR__.'/../database/migrations')
            ->seeder(SeoSeeder::class)
            ->tables(['seo_meta', 'seo_settings']),
    );
}
```

**Boot, not register.** This library binds its registry as a singleton during its own
`register()`, and provider order is not a consuming package's to choose. Registering
earlier can land the declaration in an instance that is later discarded, and the package
then silently reports its database as up to date. All ten first-party packages do it in
`packageBooted()` for that reason.

Registering does no work of any kind: it reads no database, runs nothing, and touches no
schema.

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

### Shipping a release

At the end of any change to a consuming package, three things and no more:

1. Bump `version` in that package's `composer.json`. That is the code version, and the
   version a successful run records as the database's new version.
2. Add one row to its manifest, keyed by the version you just wrote.
3. Answer `seed`: does this release need the package's seeder run to be correct? If yes,
   the package must declare a seeder, and that seeder must be idempotent.

Bump the version before or with the manifest row. A row above the deployed code version
is not reported as owed, so a row added ahead of the bump is simply invisible until the
bump lands.

Nothing here mentions schema. Writing the migration file is the declaration.

### Four rules for a consuming package

Each of these is invisible in the code and expensive to break, and none of them will
fail on a developer's own site — which is never more than one release behind.

**Old migration files are history. They are never edited and never deleted.** A database
several versions behind can only climb if every historical step is still on disk in the
current release. Folding an old migration's effect into a create-table file, or editing
one to match a newer schema, silently strands every site that has not yet run it. New
schema work is always a new file.

**Never publish a first-party package's migrations.** The whole mechanism rests on
Laravel's ledger and this library's directory glob agreeing on a migration's *name*.
Spatie's `runsMigrations()` keeps them in step by loading the package's own files under
their bare basenames. `vendor:publish --tag=<package>-migrations` writes a freshly
timestamped copy into the application's own migration directory; Laravel records the
timestamped name, the glob still computes the original basename, the two diverge — and
that package reports as owing its entire history on a database that already has the
schema.

**This library must never grow a map from migration file to release.** It would be a
third hand-maintained copy of a fact the ledger already holds, drifting the same way the
rejected schema flag would, and it would buy nothing: the operator already sees the
version gap from the stored and code versions, and the size of the job from the row
counts of the declared tables.

**Never require `qcentic-edge/filament-installer` from a package.** The dependency arrow
points at this library and never at the installer. A package reports itself whether or
not an installer is present — that is the entire point of the arrangement.

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
| `$migrationPath` | `?string` | the directory this package's own migrations live in; `null` when it declared none |
| `$seederClass` | `?string` | the one idempotent seeder it declared; `null` when it declared none |
| `codeVersionKnown()` | `bool` | whether Composer knows the deployed version, so there is one to advance the stored version to |
| `isBroken()` | `bool` | this package's own manifest could not be read; `$problem` says how |
| `owesWork()` | `bool` | broken, schema owed, or seed owed |
| `runnable()` | `bool` | whether `run()` would go ahead rather than refuse before touching anything |
| `refusal()` | `?UnrunnablePackage` | the refusal a run would make, built and handed back unthrown; `null` when it would go ahead |
| `unrunnableReason()` | `?string` | that refusal's message, fit to show an operator; `null` when it would go ahead |
| `needsAttention()` | `bool` | owes work and cannot run it — the third state, and the one with no button |
| `behindSummary()` | `string` | how far behind this package's database is, in a sentence fit to show an operator; `Database update pending` when the gap is not a version gap |

The report is the only way to read update state. Nothing else in the system queries it
directly, and nothing reimplements any part of it — the runner included, which is why a
status carries `$migrationPath` and `$seederClass`. Those are the only two facts a run
needs that are not an obligation, and a runner that read them off the registry itself
would be a second view of what a package declared, beside this one.

There is deliberately no accessor for the version ledger. Reading update state is
`report()`; the only thing that writes it is `run()`. A public read/write path beside
those two would be the same hole one level up, and a writable one would let a caller
record a version no run ever earned.

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
would be a second view of update state beside this one. So the report answers both, and
`refusal()` builds the very `UnrunnablePackage` a run would throw and hands it back
unthrown: the runner throws that object, a renderer prints its message. They cannot be
worded differently, because there is only one of them. `needsAttention()` names the state a renderer
actually branches on: owes work, has nothing runnable, show the reason instead of a button.

**Sentences both renderers show are worded here.** `unrunnableReason()` and
`behindSummary()` are on the status for the same reason `needsAttention()` is: there are
two renderers of this report — the installer's Updates page and this library's topbar
notice — in two repositories and two templating languages, and a sentence copied into both
is two sentences waiting to disagree. `behindSummary()` also carries the judgement that
goes with the wording: a version gap is not the only way to owe work, so a package with
unapplied migrations and no pending release still gets a sentence rather than
"0 releases behind".

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
except the first is the object `PackageStatus::refusal()` hands a renderer, so what an
operator is shown and what a run refuses with are not two strings that agree, they are
one string:

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

**A host that cannot draw the notice gets no notice, and no complaint.** The notice is a
Livewire component drawn into a Filament panel, so the provider registers it only where
both are actually there to draw it — installed *and* their providers registered, which is
two questions to the container and neither of them a `class_exists()`: a package can be
installed with its provider unregistered, and that is the host that crashes. Most packages take this library for its reporting alone, and their own
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

**A report that cannot be read costs the notice, never the panel.** This hook renders into
the topbar of every page, and building the report touches the database before it has said
anything — the ledger asks whether its table exists, the migration diff asks the migrator
whether its repository does. Unguarded, a database blip would not lose a badge, it would
return a 500 for every screen in the panel, on exactly the sites that have no installer and
so no other updates surface. Both reads are guarded: the hook's, and the component's. A
failed read is not silence either — silence reads as "every package is level", which is the
one thing this library must never say about a database it could not ask — so the notice
renders a single badge saying the update status is unavailable, carrying the reason in its
tooltip and no button. A user who may not see update state does not see that badge either.

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

The ledger is internal, and there is no way to reach it from outside the library. What a
package's database is at is read through `report()`:

```php
PluginUpdates::report()->status('qcentic-edge/filament-seo')->storedVersion; // '0.6.0'
```

and the only thing that ever writes it is a successful `run()`. A package the ledger has
never heard of reads back as `null`, never as an assumed version — and so does every
package on a database that has never seen the ledger table.

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

The `Host` suite boots three other shapes of application, because the library has to hold
in all of them: one with the library's provider and nothing else, which is what a consuming
package's own test app looks like; one with Livewire but no Filament, which has one half of
what the notice needs and not the other; and one where the library's provider never
registered at all.

## Changelog

No tagged releases yet.

## Contributing

Private first-party library — internal contributors only, for now.

## License

Unpublished. All rights reserved until the free-vs-paid decision lands (ADR 0005).
