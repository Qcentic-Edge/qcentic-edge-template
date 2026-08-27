<?php

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UnreachableRegistry;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\ProviderlessTestCase;

/**
 * The library's own service provider never registered.
 *
 * Left alone this is the quietest failure the library has: the registry is a
 * concrete class, so the container auto-wires one, the declaration lands in it,
 * the object is discarded, and every package afterwards reports its database as
 * up to date. That is the stale-schema-read-as-healthy failure the whole design
 * exists to prevent, and it has to fail where the mistake is.
 */
uses(ProviderlessTestCase::class);

it('is the application this test describes', function () {
    expect($this->app->getLoadedProviders())->not->toHaveKey(PluginUpdatesServiceProvider::class)
        ->and($this->app->bound(PackageRegistry::class))->toBeFalse();
});

it('refuses a registration that cannot reach the registry', function () {
    expect(fn () => PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-plugin')
            ->title('Fixture Plugin')
            ->manifest(fixturePackagePath('updates.php')),
    ))->toThrow(UnreachableRegistry::class);
});

it('names the missing provider and how to get it back', function () {
    expect(fn () => PluginUpdates::registry())
        ->toThrow(UnreachableRegistry::class, PluginUpdatesServiceProvider::class);

    expect(fn () => PluginUpdates::registry())
        ->toThrow(UnreachableRegistry::class, 'composer dump-autoload');
});

it('refuses a read of the registry rather than answering it empty', function () {
    // An empty answer is the dangerous one: it reads as "every package is level"
    // to anything that renders update state.
    expect(fn () => PluginUpdates::packages())->toThrow(UnreachableRegistry::class)
        ->and(fn () => PluginUpdates::package('qcentic-edge/fixture-plugin'))
        ->toThrow(UnreachableRegistry::class);
});

it('keeps the quiet skip and the loud refusal apart', function () {
    // Both conditions hold in this application at once: there is no Livewire to
    // render a notice with, and no registry to declare into. They get opposite
    // answers on purpose — the surface is skipped without a word, the lost
    // declaration is refused out loud — and neither answer is reached through
    // the other.
    expect(FilamentView::hasRenderHook(PanelsRenderHook::TOPBAR_END))->toBeFalse()
        ->and(fn () => PluginUpdates::registry())->toThrow(UnreachableRegistry::class);
});
