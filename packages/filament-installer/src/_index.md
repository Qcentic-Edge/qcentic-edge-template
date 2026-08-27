# src

Package PHP. The install flow is plain routes and Blade; the Updates page is the
optional Filament half.

## Files
- `FilamentInstallerServiceProvider.php` — config, views, routes, migration, `web` middleware, and the package's own `plugin-updates` declaration
- `FilamentInstallerPlugin.php` — optional panel plugin (id `installer`), registers the Updates page

## Subdirectories
- `Support/` — `InstallerState`: checks, `migrate()`, seeders, first user, DB lock
- `Http/Controllers/` — `InstallController` (`/install` show, run, check)
- `Http/Middleware/` — `RedirectToInstaller`
- `Filament/Pages/` — `Updates`: one row per registered package from `PluginUpdates::report()`, per-package update button, sidebar badge; both reads of the report are guarded so a database blip cannot 500 the panel
- `Events/` — `InstallerUserCreated`
