<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;

/**
 * A panel for the notice to render into. The library ships no panel of its
 * own and never will; this one belongs to the test app, standing in for the
 * host's.
 */
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin');
    }
}
