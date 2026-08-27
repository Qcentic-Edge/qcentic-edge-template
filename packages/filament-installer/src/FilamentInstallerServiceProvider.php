<?php

namespace QcenticEdge\FilamentInstaller;

use Illuminate\Contracts\Http\Kernel;
use QcenticEdge\FilamentInstaller\Http\Middleware\RedirectToInstaller;
use QcenticEdge\FilamentInstaller\Support\InstallerState;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
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
        // Before the retire gate: a retired installer is a finished first run,
        // which is exactly the site whose Updates page matters most.
        $this->declareUpdates();

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

    /**
     * The installer is a package like the nine it renders, and declares itself
     * on the same terms — so it appears in its own list rather than being the
     * one plugin whose database nobody is watching.
     *
     * Declared in boot rather than register: the library binds its registry as
     * a singleton in its own `register()`, and provider order is not ours to
     * choose, so registering earlier could add to an instance that is later
     * discarded.
     */
    private function declareUpdates(): void
    {
        PluginUpdates::register(
            UpdatablePackage::make('qcentic-edge/filament-installer')
                ->title('Installer')
                ->manifest(__DIR__.'/../database/updates.php')
                ->migrations(__DIR__.'/../database/migrations')
                ->tables([InstallerState::LOCK_TABLE]),
        );
    }
}
