# plugin-updates

Shared per-package database updates for first-party packages, on the WordPress model:
a package records the version its database is at, compares it against its code version,
and runs its own migrations and seeder. Deliberately not a panel plugin — no `filament-`
prefix, no Filament page, never installed directly, arrives transitively.

## Files
- `composer.json` — `qcentic-edge/plugin-updates`
- `README.md` — installation as a path repository, registering a package, the manifest, the report, running an update, the ledger
- `phpunit.xml` — Pest suite (Feature + Unit)

## Subdirectories
- `src/` — `PluginUpdates` entry point, registry, version ledger, release manifest, migration diff, the update report, and the runner that catches one package up
- `tests/` — registration, ledger, manifest parsing, the report, schema owed, the multi-version gap, running an update, the resume guarantee, never-on-boot, not-a-panel-plugin guards
