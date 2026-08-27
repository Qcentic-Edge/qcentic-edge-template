# src

Package PHP. The install flow is plain routes and Blade; the Updates page is the
optional Filament half.

## Files
- `FilamentInstallerServiceProvider.php` — config, views, routes, migration, `web` middleware
- `FilamentInstallerPlugin.php` — optional panel plugin, registers the Updates page

## Subdirectories
- `Support/` — `InstallerState`: checks, `migrate()`, `pendingMigrations()`, DB lock
- `Http/Controllers/` — `InstallController` (`/install` show, run, check)
- `Http/Middleware/` — `RedirectToInstaller`
- `Filament/Pages/` — `Updates`: pending migrations, one-click run, sidebar badge
- `Events/` — `InstallerUserCreated`
