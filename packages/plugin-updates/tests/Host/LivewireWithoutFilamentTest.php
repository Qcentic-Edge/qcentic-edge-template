<?php

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;
use QcenticEdge\PluginUpdates\Tests\LivewireOnlyTestCase;

/**
 * A host with Livewire and no Filament.
 *
 * The notice needs both — a Livewire component drawn into a panel's topbar —
 * and this is the host that has exactly one of them. A guard that asked only
 * about Livewire said yes here and went on to register a render hook against a
 * panel package whose providers had never run.
 *
 * Ordinary rather than exotic: an app can use Livewire for its own screens, or
 * list Filament's providers by hand and leave one out, and neither is a reason
 * for a package's declaration of what its database owes to fail.
 */
uses(LivewireOnlyTestCase::class);

it('is the application this test describes', function () {
    expect($this->app->getLoadedProviders())->toHaveKey(PluginUpdatesServiceProvider::class)
        ->and($this->app->bound('livewire'))->toBeTrue()
        ->and($this->app->bound('filament'))->toBeFalse();
});

it('registers no topbar notice where there is no panel to draw it in', function () {
    expect(FilamentView::hasRenderHook(PanelsRenderHook::TOPBAR_END))->toBeFalse();
});

it('registers no Livewire component either, having nothing to render it from', function () {
    // Livewire is here, so the registration would have succeeded — which is
    // exactly why the guard has to ask the other half of the question too. A
    // component nothing can reach is not a harmless leftover; it is the half
    // of the notice that got registered before the half that crashes.
    expect(app('livewire.finder')->resolveClassComponentClassName('plugin-updates.notice'))->toBeNull();
});

it('still lets a package declare itself and report what it owes', function () {
    // The skip is about a surface and never about a declaration. Reporting is
    // what most packages take this library for, and it works here exactly as it
    // does in a panel.
    registerFixturePackage();

    expect(PluginUpdates::package('qcentic-edge/fixture-plugin'))->not->toBeNull()
        ->and(PluginUpdates::report()->status('qcentic-edge/fixture-plugin'))->not->toBeNull();
});
