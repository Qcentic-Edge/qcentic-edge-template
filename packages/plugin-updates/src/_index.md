# src

Registration, the version ledger, the one reading seam over them, and the one action that
changes anything. Reading and running are separate: everything reads through the report,
and only an explicit call to `run()` ever touches the schema.

## Files
- `PluginUpdates.php` — static entry point a package calls from its service provider; also `report()`, the only way to read update state, and `run()`, the only way to change it. Every registry call goes through `registry()`, which refuses rather than auto-wiring a throwaway when the library's own provider is missing. There is no accessor for the version ledger on purpose
- `PluginUpdatesServiceProvider.php` — Spatie's `PackageServiceProvider`, for the views the notice renders; binds the registry and the ledger as singletons, and the report and the runner as fresh reads per resolve; registers the notice's Livewire component and its topbar render hook only where the host has both Livewire and Filament to draw them with. Nothing on boot touches the database

## Subdirectories
- `Registry/` — `UpdatablePackage` (what a package declares), `PackageRegistry` (all of them), `IncompleteDeclaration` (registering without a manifest), `UnreachableRegistry` (registering with no library provider to register into)
- `Ledger/` — `VersionLedger`: the `plugin_update_versions` table, ensured on first write, never on a read
- `Manifest/` — `ReleaseManifest` (seeds per release, ordered by version not by string), `UnreadableManifest`
- `Schema/` — `PendingMigrations`: one package's migration path diffed against Laravel's own ledger
- `Report/` — `UpdateReport` (the reading seam, which surfaces an unreadable manifest as that one package's problem), `PackageStatus` (what one package owes, where its migrations and seeder are, whether a run would be refused and why, with row counts resolved only when asked; also the sentences both renderers show — `unrunnableReason()` and `behindSummary()` — worded once so two renderers cannot word them differently), `TableCount`
- `Runner/` — `UpdateRunner`: one package's migrations, seeder and stored version, in that order, only on an explicit call, and entirely from what the report says; `UnrunnablePackage` (the refusals, and where each is worded — `PackageStatus::refusal()` builds the very exception a run throws, so a renderer and a run cannot disagree)
- `Notice/` — the library's own renderer, for a site with no installer: `TopbarNotice` (whether to render at all — suppressed where the installer's panel plugin is registered), `UpdatesNotice` (the Livewire component that names each package owing work and carries the action). Both read the report inside a guard: this hook is on every page of the panel, so an unreadable report costs one badge and never the panel, and says so rather than going quiet
- `Access/` — `Operator`: who may see that a package is behind and bring it up to date, the same rule the installer's Updates page uses
- `Support/` — `CodeVersion`: the deployed version, via Composer's installed-versions API
