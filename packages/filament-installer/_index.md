# filament-installer

First-run web installer plus the post-install Updates page. Built for stateless hosts:
no shell, no lock file on disk, every step through the browser.

The Updates page is a renderer over `qcentic-edge/plugin-updates`: every registered
package on its own row, updated on its own. This package scans no other package's
migrations, and registers itself with the library like any other package.

## Files
- `composer.json` — `qcentic-edge/filament-installer`
- `README.md` — install, env gates, host wiring, Updates page
- `phpunit.xml` — PHPUnit suite

## Subdirectories
- `src/` — installer state, routes/controller, middleware, panel plugin and Updates page
- `config/` — `installer.php`: enabled gate, seeders, user model
- `routes/` — `web.php`: `/install`
- `resources/views/` — install and complete screens, plus the Updates page
- `database/` — `migrations/installer_locks`, and `updates.php`: this package's own release manifest (seeds only)
- `tests/` — installer flow, checklist, Updates page against the update report
