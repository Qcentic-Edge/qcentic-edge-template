<?php

namespace Mamenein\FilamentInstaller\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallerState
{
    public static function isInstalled(): bool
    {
        return file_exists((string) config('installer.lock_file'));
    }

    public static function lock(): void
    {
        $file = (string) config('installer.lock_file');

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, now()->toIso8601String());
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

        \Mamenein\FilamentInstaller\Events\InstallerUserCreated::dispatch($user);
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
            $migrator = app('migrator');
            $repository = $migrator->getRepository();
            $ran = $repository->repositoryExists() ? $repository->getRan() : [];
            $pending = array_diff($migrator->getMigrationFiles($migrator->paths()), $ran);
            $count = count($pending);

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
