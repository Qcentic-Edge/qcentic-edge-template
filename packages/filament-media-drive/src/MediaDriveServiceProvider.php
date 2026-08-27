<?php

namespace QcenticEdge\FilamentMediaDrive;

use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MediaDriveServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-media-drive';

    public static string $viewNamespace = 'filament-media-drive';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        $this->declareUpdates();

        // Assets belong here when the plugin ships CSS/JS. Grid/list is Livewire-only.
    }

    /**
     * Tell qcentic-edge/plugin-updates what this package owes, so the operator
     * sees it in the panel's list on a host with no shell. Declared in boot
     * rather than register: the library binds its registry as a singleton in
     * its own `register()`, and provider order is not ours to choose, so
     * registering earlier could add to a throwaway instance.
     *
     * The dependency arrow points at the library and never at the installer —
     * this package reports itself whether or not an installer is present.
     *
     * No migration path, no seeder and no tables: the drive browses and picks
     * media that Spatie's media library owns, and creates no schema of its
     * own. It declares itself anyway so the operator sees every package the
     * site ships, saying it owes nothing rather than being invisible.
     */
    private function declareUpdates(): void
    {
        PluginUpdates::register(
            UpdatablePackage::make('qcentic-edge/filament-media-drive')
                ->title('Media Drive')
                ->manifest(__DIR__.'/../database/updates.php'),
        );
    }
}
