<?php

namespace QcenticEdge\FilamentInstaller\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use QcenticEdge\FilamentInstaller\FilamentInstallerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentInstallerPlugin::make());
    }
}
