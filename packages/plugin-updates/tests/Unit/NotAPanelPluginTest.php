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

it('registers no Filament page, resource, widget or navigation item', function () {
    // Namespaced references, so this catches an import or a class name while
    // leaving prose in a comment alone.
    //
    // Widgets are on this list because the criterion is navigation-visible
    // surfaces, and a dashboard widget is one: it appears on a panel page
    // without the operator asking for it, exactly as a navigation item does.
    // Taking a Filament dependency for a render hook is what narrowed this
    // guard once; it is not licence to grow a second surface behind it.
    expect(librarySource())
        ->not->toContain('Filament\\Pages\\')
        ->not->toContain('Filament\\Resources\\')
        ->not->toContain('Filament\\Widgets\\')
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

/**
 * The report is the only way to read update state, and `run()` the only way to
 * change it. That holds because three classes are reachable from almost
 * nowhere: the registry of what packages declared, the ledger of what version
 * each database is at, and Laravel's migrator. Anything that got hold of one of
 * them could answer "what does this package owe" for itself, and a second
 * answer beside the report's is how an operator ends up shown one thing while a
 * run decides another.
 *
 * It was true before this guard existed, which is precisely the problem: an
 * unguarded property drifts silently, one convenient import at a time. Each
 * entry below is a file that has business with one of the three and a reason
 * for it. Adding a file to a list is a deliberate act; arriving here by
 * accident is what this catches.
 *
 * Namespaced references again, so prose about the ledger stays free.
 */
it('keeps the registry, the ledger and the migrator behind the one seam', function () {
    $allowed = [
        // The registry holds what packages declared. `PluginUpdates` reaches it
        // because registration is its own seam — a package declaring itself —
        // and the provider binds it. Reading it as update state is the report's
        // job alone; notably the runner is not on this list, and asks the
        // report instead.
        'Registry\\PackageRegistry' => [
            'PluginUpdates.php',
            'PluginUpdatesServiceProvider.php',
            'Report/UpdateReport.php',
        ],

        // The ledger is update state itself. The report reads it, the runner
        // writes it at the end of a successful run, and the provider binds it.
        // `PluginUpdates` is deliberately absent: a public accessor there was a
        // read/write path around the seam, and this is what keeps it gone.
        'Ledger\\VersionLedger' => [
            'PluginUpdatesServiceProvider.php',
            'Report/UpdateReport.php',
            'Runner/UpdateRunner.php',
        ],

        // The migrator answers what schema work is outstanding and applies it.
        // `PendingMigrations` is the reading half — the diff the report is
        // built on — and the runner is the running half. A renderer that got
        // hold of it could scan migrations itself, which is the global scan
        // this library exists to replace.
        'Migrations\\Migrator' => [
            'Schema/PendingMigrations.php',
            'Runner/UpdateRunner.php',
        ],
    ];

    foreach ($allowed as $class => $files) {
        $found = array_keys(array_filter(
            librarySourceFiles(),
            fn (string $path): bool => str_contains(file_get_contents($path), $class),
        ));

        expect($found)->toEqualCanonicalizing($files, "[{$class}] is referenced outside the seam");
    }
});
