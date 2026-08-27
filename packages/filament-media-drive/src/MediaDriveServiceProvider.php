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
        // Assets belong here when the plugin ships CSS/JS. Grid/list is Livewire-only.

        // Declared on boot rather than on register: the registry is bound as a
        // singleton by the library's own provider, and provider registration
        // order is whatever package discovery happens to produce. Registering
        // during register() could build the registry before that binding exists
        // and lose the declaration when the singleton replaced it.
        //
        // No migration path, no seeder and no tables: the drive browses and
        // picks media that Spatie's media library owns, and creates no schema
        // of its own. It declares itself anyway so the operator sees every
        // package the site ships, saying it owes nothing rather than being
        // invisible.
        PluginUpdates::register(
            UpdatablePackage::make('qcentic-edge/filament-media-drive')
                ->title('Media Drive')
                ->manifest(__DIR__.'/../database/updates.php'),
        );
    }
}
