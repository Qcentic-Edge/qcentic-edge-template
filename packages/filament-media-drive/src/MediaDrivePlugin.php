<?php

namespace QcenticEdge\FilamentMediaDrive;

use Filament\Contracts\Plugin;
use Filament\Panel;
use QcenticEdge\FilamentMediaDrive\Pages\DrivePage;

class MediaDrivePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'media-drive';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            DrivePage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
