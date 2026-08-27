<?php

use Illuminate\Support\Facades\DB;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Report\PackageStatus;

it('is empty until a package registers', function () {
    expect(PluginUpdates::report()->all())->toBe([])
        ->and(PluginUpdates::report()->anythingOwed())->toBeFalse();
});

it('reports every registered package, keyed by name', function () {
    registerHistoryPackage();
    registerFixturePackage();

    expect(array_keys(PluginUpdates::report()->all()))
        ->toBe([HISTORY_PACKAGE, 'qcentic-edge/fixture-plugin']);
});

it('reports a package that was never registered as absent', function () {
    expect(PluginUpdates::report()->status('qcentic-edge/never-registered'))->toBeNull();
});

it('answers the whole question in one place', function () {
    registerHistoryPackage();
    placeHistoryAt('0.1.0');
    DB::table('history_widgets')->insert([['name' => 'first'], ['name' => 'second']]);

    $status = historyStatus();

    expect($status)->toBeInstanceOf(PackageStatus::class)
        ->and($status->name)->toBe(HISTORY_PACKAGE)
        ->and($status->title)->toBe('History Plugin')
        ->and($status->installedVersion)->toBe('0.1.0')
        ->and($status->codeVersion)->toBeNull()
        ->and($status->versionsBehind())->toBe(4)
        ->and($status->schemaOwed())->toBeTrue()
        ->and($status->seedOwed())->toBeTrue()
        ->and($status->owesWork())->toBeTrue()
        ->and(array_map(fn ($table) => $table->name, $status->tables))
        ->toBe(['history_widgets', 'history_notes', 'history_tags'])
        ->and($status->tables[0]->rows)->toBe(2);
});

it('reports the deployed code version', function () {
    // Registered under this library's own Composer name, which is the one
    // package guaranteed to be installed while its own suite is running.
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/plugin-updates')
            ->title('Plugin Updates')
            ->manifest(historyPackagePath('updates.php')),
    );

    expect(PluginUpdates::report()->status('qcentic-edge/plugin-updates')->codeVersion)
        ->toBe(libraryComposer()['version']);
});

it('owes nothing when the stored version is the code version and the path is fully applied', function () {
    // The installed-package fixture declares exactly one release, the version
    // this library's own composer.json carries — so stored, code and newest
    // release all land on the same point.
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/plugin-updates')
            ->title('Plugin Updates')
            ->manifest(installedPackagePath('updates.php'))
            ->migrations(historyPackagePath('migrations')),
    );

    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record('qcentic-edge/plugin-updates', libraryComposer()['version']);

    $status = PluginUpdates::report()->status('qcentic-edge/plugin-updates');

    expect($status->installedVersion)->toBe($status->codeVersion)
        ->and($status->versionsBehind())->toBe(0)
        ->and($status->pendingMigrations)->toBe([])
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->owesNothing())->toBeTrue()
        ->and(PluginUpdates::report()->anythingOwed())->toBeFalse();
});

it('counts the rows of every table a package declared', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');

    DB::table('history_widgets')->insert([['name' => 'one'], ['name' => 'two'], ['name' => 'three']]);
    DB::table('history_notes')->insert([['body' => 'a']]);

    $tables = collect(historyStatus()->tables)->keyBy(fn ($table) => $table->name);

    expect($tables['history_widgets']->rows)->toBe(3)
        ->and($tables['history_notes']->rows)->toBe(1)
        ->and($tables['history_tags']->rows)->toBe(0)
        ->and($tables['history_tags']->exists())->toBeTrue();
});

it('reports a declared table that does not exist yet as absent rather than throwing', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.1.0');

    $tables = collect(historyStatus()->tables)->keyBy(fn ($table) => $table->name);

    expect($tables['history_widgets']->exists())->toBeTrue()
        ->and($tables['history_widgets']->rows)->toBe(0)
        ->and($tables['history_notes']->exists())->toBeFalse()
        ->and($tables['history_notes']->rows)->toBeNull()
        ->and($tables['history_tags']->exists())->toBeFalse();
});

it('reports no tables for a package that declared none', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-tableless')
            ->title('Fixture Tableless')
            ->manifest(fixturePackagePath('updates.php')),
    );

    expect(PluginUpdates::report()->status('qcentic-edge/fixture-tableless')->tables)->toBe([]);
});

it('lists only the packages that owe work', function () {
    registerHistoryPackage();
    placeHistoryAt('0.1.0');

    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-quiet')
            ->title('Fixture Quiet')
            ->manifest(fixturePackagePath('updates.php')),
    );
    PluginUpdates::ledger()->record('qcentic-edge/fixture-quiet', '0.3.0');

    expect(array_keys(PluginUpdates::report()->owing()))->toBe([HISTORY_PACKAGE])
        ->and(PluginUpdates::report()->anythingOwed())->toBeTrue();
});

it('reports a package with no stored version as never having recorded one', function () {
    registerHistoryPackage();

    expect(historyStatus()->installedVersion)->toBeNull();
});
