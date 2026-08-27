# plugin-updates

Shared per-package database updates for first-party packages, on the WordPress model:
a package records the version its database is at, compares it against its code version,
and runs its own migrations and seeder. Deliberately not a panel plugin — no `filament-`
prefix, no Filament page, never installed directly, arrives transitively.

## Files
- `composer.json` — `qcentic-edge/plugin-updates`
- `README.md` — installation as a path repository, registering a package, the manifest, the ledger
- `phpunit.xml` — Pest suite (Feature + Unit)

## Subdirectories
- `src/` — `PluginUpdates` entry point, registry, version ledger, Composer version lookup
- `tests/` — registration, ledger, code-version resolution, not-a-panel-plugin guards
