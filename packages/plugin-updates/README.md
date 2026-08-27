# plugin-updates

`qcentic-edge/plugin-updates` gives every first-party package the same answer to one
question: has the code moved ahead of the database, and what does it owe?

The model is WordPress'. A package keeps its own version, compares it against the
version of the code that is deployed, and runs its own migrations and seeder. Nothing
central runs a package's schema work, and no package depends on the installer to
finish its own upgrade.

This is a library, not a panel plugin. It drops the `filament-` prefix, ships no
Filament page, resource or navigation item, and is never installed directly — it
arrives transitively as a dependency of the packages that use it.

## Registering a package

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

Package name, title and manifest are what every package declares. The migration path,
the seeder and the tables are optional: a package may own no schema, no seed data or
no tables at all, and the library refuses to guess at any of them. Declaring tables is
what lets the operator see row counts without the package writing reporting code.

Registering the same package name twice replaces the declaration rather than listing
the package twice.

## The manifest

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

There is no assets flag: assets are overwritten wholesale by the deploy and can never
be owed at runtime.

Old migration files are history. They are never edited and never deleted, because a
database several versions behind can only climb if every historical step is still on
disk in the current release.

## The version ledger

```php
PluginUpdates::ledger()->record('qcentic-edge/filament-seo', '0.6.0');
PluginUpdates::ledger()->storedVersion('qcentic-edge/filament-seo'); // '0.6.0'
```

A package the ledger has never heard of reads back as `null`, never as an assumed
version.

The ledger's table, `plugin_update_versions`, ships as no migration file of its own
and cannot: it has to exist before the machinery that runs migrations can report
anything, and on a stateless edge host there is no shell to create it with. It is
ensured idempotently behind a table-existence check on first use, which behaves
identically whether the installer is present, absent, or added later.

## Code version

The deployed version comes from Composer's installed-versions API, which resolves
path repositories correctly — a path package reports the version its own
`composer.json` declares.

```php
PluginUpdates::package('qcentic-edge/filament-seo')->codeVersion();
```

## Tests

```bash
composer install
composer test
```
