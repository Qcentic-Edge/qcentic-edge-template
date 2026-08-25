<?php

namespace Mamenein\FilamentInstaller;

use Illuminate\Contracts\Http\Kernel;
use Mamenein\FilamentInstaller\Http\Middleware\RedirectToInstaller;
use Mamenein\FilamentInstaller\Support\InstallerState;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentInstallerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'installer';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigration('create_installer_locks_table')
            ->runsMigrations();
    }

    public function packageBooted(): void
    {
        // INSTALLER_ENABLED=false: no redirects, app is fully open.
        if (InstallerState::isRetired()) {
            return;
        }

        // Database session/cache stores need tables that only exist after
        // migrate. Until the DB lock row exists, use drivers that need no schema
        // (cookie sessions work on ephemeral Magic Container disks).
        if (! InstallerState::isInstalled()) {
            config([
                'session.driver' => 'cookie',
                'cache.default' => 'array',
            ]);

            if ($this->app->bound('session')) {
                $this->app->forgetInstance('session');
                $this->app->forgetInstance('session.store');
            }
        }

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', RedirectToInstaller::class);
    }
}
