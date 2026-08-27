<?php

namespace QcenticEdge\PluginUpdates\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;
use QcenticEdge\PluginUpdates\Tests\Fixtures\TestPanelProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    /**
     * The library renders into a panel's topbar, so its test app is a panel:
     * Filament, Livewire and one panel provider. Listed rather than discovered,
     * because Testbench does not discover a package's own dependencies'
     * providers for it — and listed in the order Composer's package discovery
     * would produce, which Filament depends on: its support provider rebinds a
     * Livewire mechanism, and rebinding one Livewire has already registered
     * leaves that mechanism stateless.
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            TestPanelProvider::class,
            PluginUpdatesServiceProvider::class,
        ];
    }

    /**
     * Livewire signs the payloads the notice's action rides on, so the test app
     * needs a key the way any app does. Fixed rather than generated: nothing
     * here is a secret, and a stable key keeps a failure reproducible.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('plugin-updates!!', 2)));
    }
}
