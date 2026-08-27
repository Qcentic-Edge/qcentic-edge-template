# Filament Installer

First-run web installer for the Qcentic Edge stack on **stateless** hosts
(Magic Containers, ephemeral disks, multi-replica). Until setup is finished,
every web request redirects to `/install`.

## How “done” works

Two gates — neither is a file on the container disk:

1. **Database lock** — after migrate → seeders → optional first user, a row is
   written to `installer_locks`. Shared across every replica.
2. **Env retire** — set `INSTALLER_ENABLED=false` and redeploy. That opens the
   app for good. The complete page asks for this and has a **Check** button.

| State | Behaviour |
| --- | --- |
| `INSTALLER_ENABLED=true`, no DB lock | Checklist + Run |
| `INSTALLER_ENABLED=true`, DB lock set | Complete page (set env, Check) |
| `INSTALLER_ENABLED=false` | Middleware off; app open |

Built for headless container targets where there is no shell and no persistent
local filesystem.

## Install

```bash
composer require qcentic-edge/filament-installer
```

Private GitHub: add a VCS repository pointing at
`https://github.com/Qcentic-Edge/filament-installer.git`.

The service provider auto-registers and runs the package migration
(`installer_locks`). Host apps that assign Spatie roles must also wire
`installer.seeders` and listen for `InstallerUserCreated` (see below).

## Behavior

- `GET /install` — checklist while unlocked; complete page after DB lock.
- `POST /install` — migrate → configured seeders → optional first user → DB lock → complete page.
- `POST /install/check` — if `INSTALLER_ENABLED=false`, redirect home; else stay on complete with an error.
- Vite, Livewire (including hashed paths), and `/up` are never redirected.
- While unlocked: forces `session.driver=cookie` and `cache.default=array` so
  `SESSION_DRIVER=database` / `CACHE_STORE=database` do not 500 on missing tables
  before migrate.
- First-user create is idempotent by email (safe retry after a partial failure).
- DB lock insert is idempotent (safe if two replicas finish at once).

## Host app wiring (required for Shield / Spatie)

```php
// AppServiceProvider::register()
$this->app->booting(function (): void {
    config([
        'installer.seeders' => [
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PassportClientSeeder::class, // if Passport PATs are used
        ],
    ]);
});

// AppServiceProvider::boot()
Event::listen(InstallerUserCreated::class, function (InstallerUserCreated $event): void {
    if (! method_exists($event->user, 'assignRole')) {
        return;
    }

    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $event->user->assignRole('super_admin');
});
```

Without `installer.seeders`, `assignRole('super_admin')` fails with
`There is no role named super_admin for guard web`.

## Magic Containers checklist

1. Set `INSTALLER_ENABLED=true` for the first deploy.
2. Env must include `APP_KEY`, `APP_URL` (no trailing slash), `DB_CONNECTION`,
   `DB_URL`, and (for remote libSQL) `DB_AUTH_TOKEN`.
3. `SESSION_DRIVER=database` and `CACHE_STORE=database` are fine — the plugin
   switches to cookie/array until the DB lock exists.
4. Open `/install`, run setup, then on the complete page set
   `INSTALLER_ENABLED=false`, redeploy, press **Check** (or open `/`).
5. Passport PEMs: generate with `php artisan passport:keys`, dump as one line
   with `\n` escapes into `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`, then
   delete `storage/oauth-*.key` (ephemeral disk).

## Config

`php artisan vendor:publish --tag=installer-config` exposes:

| Key | Default | Purpose |
| --- | --- | --- |
| `installer.enabled` | `true` (`INSTALLER_ENABLED`) | master switch / retire gate |
| `installer.path` | `install` (`INSTALLER_PATH`) | installer URI |
| `installer.required_env` | `APP_KEY, APP_URL, DB_CONNECTION, DB_URL` | env vars that must be non-empty |
| `installer.create_user` | `true` (`INSTALLER_CREATE_USER`) | show name/email/password fields and create the first user |
| `installer.user_model` | `App\Models\User` (`INSTALLER_USER_MODEL`) | model used for that user |
| `installer.seeders` | `[]` | seeder classes run after migrate (e.g. `RoleSeeder`) |

## Tests

```bash
composer install
vendor/bin/phpunit
```
