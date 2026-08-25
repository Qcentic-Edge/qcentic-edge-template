# Filament Installer

First-run web installer for the Qcentic Edge stack. Until the app is installed,
every web request redirects to `/install`, which shows a live checklist of the
required environment (env vars, database reachability, writable storage, pending
migrations) and a button that runs `migrate --force`, configured seeders, and
optional first-user creation. On success it writes a lock file and the installer
disappears for good.

Built for headless container targets (e.g. Bunny Magic Containers) where there is
no shell to run migrations from.

## Install

```bash
composer require mamenein/filament-installer
```

Private GitHub: add a VCS repository pointing at
`https://github.com/Qcentic-Edge/filament-installer.git`.

The service provider auto-registers. Host apps that assign Spatie roles must
also wire `installer.seeders` and listen for `InstallerUserCreated` (see below).

## Behavior

- `GET /install` — checklist; the button stays disabled until every check passes.
- `POST /install` — migrate → configured seeders → optional first user → lock → redirect home.
- After install: `/install` returns 404 and the middleware passes through.
- Vite dev assets, Livewire messages and `/up` are never redirected.
- While unlocked: forces `session.driver=cookie` and `cache.default=array` so
  `SESSION_DRIVER=database` / `CACHE_STORE=database` do not 500 on missing tables
  before migrate.
- First-user create is idempotent by email (safe retry after a partial failure).

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
   switches to cookie/array until the lock file exists.
4. After a successful `/install`, set `INSTALLER_ENABLED=false` and redeploy to
   retire the route permanently.
5. Passport PEMs: generate with `php artisan passport:keys`, dump as one line
   with `\n` escapes into `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`, then
   delete `storage/oauth-*.key` (ephemeral disk).

## Config

`php artisan vendor:publish --tag=installer-config` exposes:

| Key | Default | Purpose |
| --- | --- | --- |
| `installer.enabled` | `true` (`INSTALLER_ENABLED`) | master switch |
| `installer.path` | `install` (`INSTALLER_PATH`) | installer URI |
| `installer.required_env` | `APP_KEY, APP_URL, DB_CONNECTION, DB_URL` | env vars that must be non-empty |
| `installer.lock_file` | `storage/app/.installer-installed` | presence = installed |
| `installer.create_user` | `true` (`INSTALLER_CREATE_USER`) | show name/email/password fields and create the first user |
| `installer.user_model` | `App\Models\User` (`INSTALLER_USER_MODEL`) | model used for that user |
| `installer.seeders` | `[]` | seeder classes run after migrate (e.g. `RoleSeeder`) |

## Tests

```bash
composer install
vendor/bin/phpunit
```
