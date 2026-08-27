<?php

/**
 * The library is deliberately not a panel plugin. It drops the `filament-`
 * prefix, ships no page, resource or navigation item, and is never installed
 * directly — it arrives transitively as a dependency of the packages that use
 * it. These assertions are what stops that drifting back.
 *
 * It does require `filament/filament`, and that is correct rather than a
 * regression: the library renders a notice into the panel's topbar and needs
 * Filament's render hooks to do it. A guard that forbade the dependency
 * outright was asserting more than the criterion says, and would have been
 * worked around rather than met. What actually makes a package a panel plugin
 * in this workstation is the `filament-` prefix, a page of its own, and being
 * installed directly — and those are what is guarded here.
 */
it('is not named as a panel plugin', function () {
    expect(libraryComposer()['name'])->toBe('qcentic-edge/plugin-updates')
        ->and(libraryComposer()['name'])->not->toContain('filament-');
});

it('registers no Filament page, resource or navigation item', function () {
    // Namespaced references, so this catches an import or a class name while
    // leaving prose in a comment alone.
    expect(librarySource())
        ->not->toContain('Filament\\Pages\\')
        ->not->toContain('Filament\\Resources\\')
        ->not->toContain('Filament\\Clusters\\')
        ->not->toContain('Filament\\Contracts\\Plugin')
        ->not->toContain('NavigationItem');
});

it('depends on the installer neither in Composer nor in code', function () {
    // The dependency arrow points from packages to this library and never to
    // the installer; inverting it is the entire point of the effort this
    // library exists for. The notice detects the installer by the id its panel
    // plugin registers under — a string, resolved at runtime — and imports
    // nothing of it.
    $composer = libraryComposer();

    expect(array_keys($composer['require'] + $composer['require-dev']))
        ->not->toContain('qcentic-edge/filament-installer')
        ->and(librarySource())->not->toContain('QcenticEdge\\FilamentInstaller');
});

it('ships no routes to register', function () {
    expect(is_dir(libraryPath('routes')))->toBeFalse();
});
