<?php

use QcenticEdge\PluginUpdates\Manifest\ReleaseManifest;
use QcenticEdge\PluginUpdates\PluginUpdates;

test('the package declares itself, owning no migrations, seeder or tables', function () {
    $package = PluginUpdates::package('qcentic-edge/filament-media-drive');

    expect($package)->not->toBeNull()
        ->and($package->displayTitle())->toBe('Media Drive')
        ->and($package->migrationPath())->toBeNull()
        ->and($package->seederClass())->toBeNull()
        ->and($package->tableNames())->toBe([]);
});

test('the manifest parses and no release owes a seed', function () {
    $manifest = ReleaseManifest::read(
        PluginUpdates::package('qcentic-edge/filament-media-drive')->manifestPath(),
    );

    expect($manifest->versions())->toBe(['0.1.0'])
        ->and($manifest->seedingAmong($manifest->versions()))->toBe([]);
});

test('the package reports as owing nothing, and asking for its row counts does not throw', function () {
    $status = PluginUpdates::report()->status('qcentic-edge/filament-media-drive');

    expect($status)->not->toBeNull()
        ->and($status->isBroken())->toBeFalse()
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->owesWork())->toBeFalse()
        ->and($status->tables())->toBe([]);
});

test('running an update on a package with no migration path does not throw', function () {
    PluginUpdates::run('qcentic-edge/filament-media-drive');

    expect(PluginUpdates::report()->status('qcentic-edge/filament-media-drive')->storedVersion)
        ->toBe(PluginUpdates::package('qcentic-edge/filament-media-drive')->codeVersion());
});
