<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Stands in for `filament-installer`'s panel plugin, which the library must
 * never import or require. All that matters to the notice is the id the
 * installer registers under, so all this fixture has is that id.
 */
class InstallerLikePlugin implements Plugin
{
    public function getId(): string
    {
        return 'installer';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
