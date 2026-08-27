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

This package depends on `qcentic-edge/plugin-updates`, which arrives transitively and is
resolved against the application's own repositories, so the app needs a path entry for it
too:

```json
{
    "type": "path",
    "url": "../packages/plugin-updates"
}
```

The service provider auto-registers and runs the package migration
(`installer_locks`). Host apps that assign Spatie roles must also wire
`installer.seeders` and listen for `InstallerUserCreated` (see below).

## Behavior

- `GET /install` — checklist while unlocked; complete page after DB lock. The checklist's
  **Package updates** line counts the packages the update library reports as owing work.
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

## Updates page (after first run)

The installer migrates once, then locks itself. When a plugin upgrade later ships a
migration there is nowhere to run it — these apps are stateless containers with no shell
and no persistent disk. Registering the panel plugin adds an **Updates** page for exactly
that, the way WordPress lists plugins whose database is behind their code:

```php
use QcenticEdge\FilamentInstaller\FilamentInstallerPlugin;

$panel->plugin(FilamentInstallerPlugin::make());
```

The page (Settings → Updates, `/updates`) lists **every package registered with
`qcentic-edge/plugin-updates`**, one row each: the version its database is at, the version
its code is at, what it owes, and the tables the work will touch with their row counts. A
row that owes work carries its own Update button, so one plugin is updated without
touching the others; a row that owes nothing says it is up to date and carries no button.
A package the library would refuse to run — an unreadable manifest, a version Composer
cannot resolve, a seed owed with no seeder declared — is shown as needing attention with
the reason, rather than a button whose only effect would be that refusal. The sidebar
badge counts the packages needing updates and clears when none do.

Both readings of the report are guarded. The badge is rendered on every page of the panel,
so a database the report cannot reach drops the badge rather than returning a 500 for the
whole panel; the page itself says the update status is unavailable, and why, rather than
rendering an empty list that would read as every plugin being up to date.

A package several releases behind is one row showing the gap, not one row per release: the
library replays the whole history in a single pass. The sentence that names the gap comes
from the report — `PackageStatus::behindSummary()` — so this page and the library's own
topbar notice describe the same gap in the same words rather than in two copies of one
cascade. A version gap on its own is never a button — a site that has simply never
recorded a stored version owes nothing, and reads as up to date.

Access is limited to `super_admin` when the user model offers `hasRole()`, and to any
authenticated panel user otherwise — the same posture the installer itself takes, and read
from `QcenticEdge\PluginUpdates\Access\Operator` so that this page and the library's own
topbar notice cannot disagree about who may run an update. Nothing locks between two
operators pressing Update at once; the confirmation says so.

This package **does not scan other packages' migrations**. It used to, and could then only
report that *something* somewhere was pending — which also meant every plugin shipping an
upgrade had to assume the installer was there to finish it. Each package now declares
itself to the shared library and this one is a renderer over the result. The installer
declares itself the same way (`database/updates.php`, `database/migrations`,
`installer_locks`) and appears in its own list.

The install flow needs no panel, so this plugin object is optional. Without it the package
is plain routes and Blade, and each package's own topbar notice is the surface an operator
sees instead.

## Config

`php artisan vendor:publish --tag=installer-config` exposes:

| Key | Default | Purpose |
| --- | --- | --- |
| `installer.enabled` | `true` (`INSTALLER_ENABLED`) | master switch / retire gate |
| `installer.path` | `install` (`INSTALLER_PATH`) | installer URI |
| `installer.required_env` | `APP_KEY, APP_URL, DB_CONNECTION, DB_URL` | env vars that must be non-empty |
| `installer.create_user` | `true` (`INSTALLER_CREATE_USER`) | show name/email/password fields and create the first user |
| `installer.user_model` | `App\Models\User` (`INSTALLER_USER_MODEL`) | model used for that user |
| `installer.seeders` | `[]` | seeder classes run after migrate (e.g. `RoleSeeder`) — first install only; per-package update seeds are the library's, not these |

## Shipping a release of this package

This package declares itself to `qcentic-edge/plugin-updates` like every other package,
so it appears in its own Updates list rather than being the one plugin whose database
nobody is watching. Shipping a release means adding one row to `database/updates.php`:

```php
return [
    '0.4.0' => ['seed' => false],
];
```

Seeds only — schema work is read from Laravel's migration ledger diffed against
`database/migrations`, so writing the migration file is the declaration. Old migration
files are history: never edited, never deleted.

## Tests

```bash
composer install
vendor/bin/phpunit
```
