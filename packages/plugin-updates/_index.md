# plugin-updates

Shared per-package database updates for first-party packages, on the WordPress model:
a package records the version its database is at, compares it against its code version,
and runs its own migrations and seeder. Deliberately not a panel plugin — no `filament-`
prefix, no Filament page, resource or navigation item, never installed directly, arrives
transitively. It does depend on Filament, because it renders one notice into the panel's
topbar for sites that have no installer plugin to render it for them — and it skips that
notice in a host without both Livewire and a Filament panel, so a package that consumes
this library for reporting alone never has to boot a panel.

## Files
- `composer.json` — `qcentic-edge/plugin-updates`
- `README.md` — installation as a path repository, registering a package (in `packageBooted()`, never `packageRegistered()`), the manifest, the end-of-release checklist and the four rules a consuming package must not break, the report, running an update, the topbar notice, the ledger
- `phpunit.xml` — Pest suite (Feature + Unit + Host)

## Subdirectories
- `src/` — `PluginUpdates` entry point, registry, version ledger, release manifest, migration diff, the update report, the runner that catches one package up, and the topbar notice
- `resources/views/` — the two Blade views the topbar notice renders — the render hook's mount point, and `livewire/notice.blade.php`, the component's own view; the library ships no others, and no routes at all
- `tests/` — registration, ledger, manifest parsing, the report, schema owed, the multi-version gap, running an update, the resume guarantee, never-on-boot, the topbar notice, the not-a-panel-plugin and one-seam guards, and the three host shapes: no panel at all, Livewire without Filament, and no library provider registered
