# filament-installer

First-run web installer plus the post-install Updates page. Built for stateless hosts:
no shell, no lock file on disk, every step through the browser.

## Files
- `composer.json` — `qcentic-edge/filament-installer`
- `README.md` — install, env gates, host wiring, Updates page
- `phpunit.xml` — PHPUnit suite

## Subdirectories
- `src/` — installer state, routes/controller, middleware, panel plugin and Updates page
- `config/` — `installer.php`: enabled gate, seeders, user model
- `routes/` — `web.php`: `/install`
- `resources/views/` — install and complete screens, plus the Updates page
- `database/migrations/` — `installer_locks`
- `tests/` — installer flow, pending-migration detection, Updates page
