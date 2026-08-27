# src

Registration, the version ledger, the one reading seam over them, and the one action that
changes anything. Reading and running are separate: everything reads through the report,
and only an explicit call to `run()` ever touches the schema.

## Files
- `PluginUpdates.php` — static entry point a package calls from its service provider; also `report()`, the only way to read update state, and `run()`, the only way to change it
- `PluginUpdatesServiceProvider.php` — binds the registry and the ledger as singletons, and the report and the runner as fresh reads per resolve; nothing else, and nothing on boot

## Subdirectories
- `Registry/` — `UpdatablePackage` (what a package declares), `PackageRegistry` (all of them), `IncompleteDeclaration` (registering without a manifest)
- `Ledger/` — `VersionLedger`: the `plugin_update_versions` table, ensured on first write, never on a read
- `Manifest/` — `ReleaseManifest` (seeds per release, ordered by version not by string), `UnreadableManifest`
- `Schema/` — `PendingMigrations`: one package's migration path diffed against Laravel's own ledger
- `Report/` — `UpdateReport` (the reading seam, which surfaces an unreadable manifest as that one package's problem), `PackageStatus` (what one package owes, with row counts resolved only when asked), `TableCount`
- `Runner/` — `UpdateRunner`: one package's migrations, seeder and stored version, in that order and only on an explicit call; `UnrunnablePackage` (the refusals it makes before touching anything)
- `Support/` — `CodeVersion`: the deployed version, via Composer's installed-versions API
