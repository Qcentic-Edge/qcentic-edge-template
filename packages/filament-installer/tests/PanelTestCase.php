<?php

namespace QcenticEdge\FilamentInstaller\Tests;

use QcenticEdge\FilamentInstaller\Tests\Fixtures\AdminPanelProvider;

abstract class PanelTestCase extends TestCase
{
    // Filament's Blade components pull in blade-icons and the Heroicons set;
    // discovery is the cheapest way to register that whole chain.
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        // Livewire must register after filament/support: SupportServiceProvider
        // re-binds Livewire's DataStore as non-shared, which breaks the shared
        // store that validation error bags rely on if it runs later.
        return [
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            ...parent::getPackageProviders($app),
            AdminPanelProvider::class,
        ];
    }
}
