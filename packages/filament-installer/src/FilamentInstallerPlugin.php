<?php

namespace QcenticEdge\FilamentInstaller;

use Filament\Contracts\Plugin;
use Filament\Panel;
use QcenticEdge\FilamentInstaller\Filament\Pages\Updates;

/**
 * Optional panel half of the installer: the Updates page.
 *
 * The install flow itself is plain routes and Blade, so an app can use this
 * package without a panel. Register this plugin to also get the Updates page —
 * one row per package registered with `qcentic-edge/plugin-updates`, what each
 * one's database owes, and a button that brings that plugin alone up to date.
 */
class FilamentInstallerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'installer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            Updates::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
