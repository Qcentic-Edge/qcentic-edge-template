# src

Registration, the version ledger, and the one reading seam over them. Nothing here runs
anything: applying migrations and seeders is a separate concern.

## Files
- `PluginUpdates.php` — static entry point a package calls from its service provider; also `report()`, the only way to read update state
- `PluginUpdatesServiceProvider.php` — binds the registry and the ledger, nothing else

## Subdirectories
- `Registry/` — `UpdatablePackage` (what a package declares), `PackageRegistry` (all of them), `IncompleteDeclaration` (registering without a manifest)
- `Ledger/` — `VersionLedger`: the `plugin_update_versions` table, ensured on first write, never on a read
- `Manifest/` — `ReleaseManifest` (seeds per release, ordered by version not by string), `UnreadableManifest`
- `Schema/` — `PendingMigrations`: one package's migration path diffed against Laravel's own ledger
- `Report/` — `UpdateReport` (the reading seam), `PackageStatus` (what one package owes), `TableCount`
- `Support/` — `CodeVersion`: the deployed version, via Composer's installed-versions API
