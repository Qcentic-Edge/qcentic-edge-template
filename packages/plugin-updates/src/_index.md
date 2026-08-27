# src

Registration and the version ledger. Nothing here reports and nothing here runs.

## Files
- `PluginUpdates.php` — static entry point a package calls from its service provider
- `PluginUpdatesServiceProvider.php` — binds the registry and the ledger, nothing else

## Subdirectories
- `Registry/` — `UpdatablePackage` (what a package declares), `PackageRegistry` (all of them), `IncompleteDeclaration` (registering without a manifest)
- `Ledger/` — `VersionLedger`: the `plugin_update_versions` table, ensured on first write, never on a read
- `Support/` — `CodeVersion`: the deployed version, via Composer's installed-versions API
