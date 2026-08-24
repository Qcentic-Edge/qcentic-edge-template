<?php

namespace Mamenein\FilamentMediaDrive;

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
    }
}
