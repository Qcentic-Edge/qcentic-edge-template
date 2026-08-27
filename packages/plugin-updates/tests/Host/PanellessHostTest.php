<?php

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;
use QcenticEdge\PluginUpdates\Registry\IncompleteDeclaration;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\PanellessTestCase;

/**
 * A host with no panel in it. The library's provider is registered; Livewire's
 * is not, and neither is Filament's.
 *
 * This is what a consuming package's own test application looks like, and the
 * library has to boot in it. The notice is an optional surface — a package
 * takes this library to say what its releases owe, and only a site with a panel
 * ever draws a topbar — so a host that cannot render it gets no notice and no
 * complaint, rather than a fatal error at boot that forces the package to list
 * Filament's and Livewire's providers in a particular order to satisfy a
 * surface it will never show.
 */
uses(PanellessTestCase::class);

it('boots the library in an application with no Livewire registered', function () {
    // The provider has already booted by the time this body runs — that is what
    // the test application does — so reaching here at all is the assertion, and
    // the rest says the host really is the one described.
    expect($this->app->getLoadedProviders())->toHaveKey(PluginUpdatesServiceProvider::class)
        ->and($this->app->bound('livewire'))->toBeFalse();
});

it('registers no topbar notice where there is nothing to render it', function () {
    expect(FilamentView::hasRenderHook(PanelsRenderHook::TOPBAR_END))->toBeFalse();
});

it('still lets a package declare itself with no panel booted', function () {
    // The reason the skip is a skip: reporting is what most packages take this
    // library for, and it works here exactly as it does in a panel.
    registerFixturePackage();

    expect(PluginUpdates::package('qcentic-edge/fixture-plugin'))->not->toBeNull();
});

it('keeps refusing a half-declared package in a host that renders nothing', function () {
    // The quiet skip is about a surface, never about a declaration. A host
    // without Livewire is still held to every rule a host with one is.
    expect(fn () => PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-manifestless')->title('Fixture Manifestless'),
    ))->toThrow(IncompleteDeclaration::class, 'qcentic-edge/fixture-manifestless');
});
