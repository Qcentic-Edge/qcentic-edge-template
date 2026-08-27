<?php

/**
 * The library is deliberately not a panel plugin. It drops the `filament-`
 * prefix, ships no page, resource or navigation item, and is never installed
 * directly — it arrives transitively as a dependency of the packages that use
 * it. These assertions are what stops that drifting back.
 */
it('requires no Filament package', function () {
    $composer = libraryComposer();

    $filament = array_filter(
        array_keys($composer['require'] + $composer['require-dev']),
        fn (string $package) => str_starts_with($package, 'filament/'),
    );

    expect($filament)->toBe([]);
});

it('registers no Filament page, resource or navigation item', function () {
    // Any real reference to a Filament class is namespaced, so this catches
    // an import or a class name while leaving prose in a comment alone.
    expect(librarySource())->not->toContain('Filament\\');
});

it('ships no views or routes to register', function () {
    expect(is_dir(libraryPath('resources')))->toBeFalse()
        ->and(is_dir(libraryPath('routes')))->toBeFalse();
});
