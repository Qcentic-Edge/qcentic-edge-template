<?php

/**
 * The library is deliberately not a panel plugin. It drops the `filament-`
 * prefix, ships no page, resource or navigation item, and is never installed
 * directly — it arrives transitively as a dependency of the packages that use
 * it. These assertions are what stops that drifting back.
 */
function librarySource(): string
{
    $source = '';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src')) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $source .= file_get_contents($file->getPathname());
        }
    }

    return $source;
}

it('requires no Filament package', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

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
    expect(is_dir(__DIR__.'/../../resources'))->toBeFalse()
        ->and(is_dir(__DIR__.'/../../routes'))->toBeFalse();
});
