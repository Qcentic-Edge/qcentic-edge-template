<?php

namespace QcenticEdge\FilamentInstaller\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallerState
{
    public const LOCK_TABLE = 'installer_locks';

    /**
     * True after migrate/seed/user succeeded (row in installer_locks).
     * Shared across replicas — no local disk.
     */
    public static function isInstalled(): bool
    {
        try {
            if (! Schema::hasTable(self::LOCK_TABLE)) {
                return false;
            }

            return DB::table(self::LOCK_TABLE)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Master switch off (INSTALLER_ENABLED=false). App is open; installer retired.
     */
    public static function isRetired(): bool
    {
        return ! (bool) config('installer.enabled', true);
    }

    public static function lock(): void
    {
        if (! Schema::hasTable(self::LOCK_TABLE)) {
            throw new \RuntimeException(
                'installer_locks table missing. Migrations must run before lock().'
            );
        }

        if (DB::table(self::LOCK_TABLE)->exists()) {
            return;
        }

        DB::table(self::LOCK_TABLE)->insert([
            'installed_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{label: string, ok: bool, detail: string|null}>
     */
    public static function checks(): array
    {
        $checks = [];

        foreach ((array) config('installer.required_env', []) as $key) {
            $ok = filled(env($key));
            $checks[] = [
                'label' => $key,
                'ok' => $ok,
                'detail' => $ok ? null : 'missing or empty',
            ];
        }

        $checks[] = self::databaseCheck();
        $checks[] = self::storageCheck();
        $checks[] = self::migrationsCheck();

        return $checks;
    }

    public static function ready(): bool
    {
        return collect(self::checks())->every(fn (array $check): bool => $check['ok']);
    }

    public static function migrate(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        return Artisan::output();
    }

    /**
     * Migration names that exist on disk but have not run yet.
     *
     * This is what the Updates page shows after first install: a plugin
     * upgrade ships a migration, and the operator runs it from the panel
     * rather than needing a shell on a stateless edge container.
     *
     * @return list<string>
     */
    public static function pendingMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $repository = $migrator->getRepository();

            if (! $repository->repositoryExists()) {
                // Nothing has run yet, so everything on disk is pending.
                return array_values(array_keys(self::migrationFiles()));
            }

            return array_values(array_diff(
                array_keys(self::migrationFiles()),
                $repository->getRan(),
            ));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Every migration the app can run: the application's own directory plus
     * the paths each package registered with loadMigrationsFrom().
     *
     * @return array<string, string>  migration name => file path
     */
    protected static function migrationFiles(): array
    {
        $migrator = app('migrator');

        return $migrator->getMigrationFiles(array_merge(
            [database_path('migrations')],
            $migrator->paths(),
        ));
    }

    /**
     * Run configured seeder classes (roles, Passport clients, …) after migrate.
     */
    public static function seed(): void
    {
        foreach ((array) config('installer.seeders', []) as $seeder) {
            if (! is_string($seeder) || $seeder === '' || ! class_exists($seeder)) {
                continue;
            }

            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
        }
    }

    public static function createUser(string $name, string $email, string $password): void
    {
        $model = (string) config('installer.user_model');

        // updateOrCreate so a failed prior attempt (e.g. missing role) can retry.
        $user = $model::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ],
        );

        \QcenticEdge\FilamentInstaller\Events\InstallerUserCreated::dispatch($user);
    }

    /**
     * @return array{label: string, ok: bool, detail: string|null}
     */
    protected static function databaseCheck(): array
    {
        try {
            DB::connection()->select('select 1');

            return ['label' => 'Database reachable', 'ok' => true, 'detail' => null];
        } catch (Throwable $e) {
            return ['label' => 'Database reachable', 'ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /**
     * @return array{label: string, ok: bool, detail: string|null}
     */
    protected static function storageCheck(): array
    {
        $path = storage_path('app');
        $ok = is_dir($path) && is_writable($path);

        return [
            'label' => 'storage/app writable',
            'ok' => $ok,
            'detail' => $ok ? null : $path.' is not writable',
        ];
    }

    /**
     * @return array{label: string, ok: bool, detail: string|null}
     */
    protected static function migrationsCheck(): array
    {
        try {
            // getMigrationFiles() is keyed by migration name and valued by
            // path, while getRan() is a list of names — diffing them directly
            // compares paths against names and reports everything as pending.
            $count = count(self::pendingMigrations());

            return [
                'label' => 'Pending migrations',
                'ok' => true,
                'detail' => $count === 0 ? 'none pending' : $count.' pending',
            ];
        } catch (Throwable $e) {
            return [
                'label' => 'Pending migrations',
                'ok' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }
}
