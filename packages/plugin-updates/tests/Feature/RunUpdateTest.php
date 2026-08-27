<?php

use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Runner\UnrunnablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\MidRunFailure;

/**
 * An operator clicks the button on a package that owes work, and the package
 * catches up: the unapplied migrations in its own path run, its seeder runs if
 * any pending release owes one, and the stored version advances to the code
 * version.
 *
 * The gap is never a special case. Every test here goes through the same call,
 * whether the database is one release behind or has the package's whole history
 * unapplied, because there is no catch-up mode to get wrong.
 */
beforeEach(fn () => MidRunFailure::disarm());

it('applies the unapplied migrations in the package own path', function () {
    placeRunAt('0.0.2');
    registerRunPackage();

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(Schema::hasTable('run_tags'))->toBeTrue()
        ->and(Schema::hasColumn('run_widgets', 'colour'))->toBeTrue()
        ->and(appliedMigrations())->toBe([
            '2026_01_01_000000_create_run_widgets_table',
            '2026_02_01_000000_create_run_notes_table',
            '2026_03_01_000000_create_run_tags_table',
            '2026_04_01_000000_add_colour_to_run_widgets_table',
        ]);
});

it('leaves the files already in the ledger alone rather than running them again', function () {
    // Re-running an applied create-table file would fail outright, so a green
    // run is most of the proof; the ledger holding one entry each is the rest.
    placeRunAt('0.0.2');
    registerRunPackage();

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(array_count_values(appliedMigrations()))->each->toBe(1);
});

it('reaches the code version in one run from a database with the whole history unapplied', function () {
    // The catch-up path and the fresh-install path are the same path: nothing
    // here distinguishes four releases behind from one.
    registerRunPackage();

    expect(runStatus()->versionsBehind())->toBe(4);

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(appliedMigrations())->toHaveCount(4)
        ->and(runStatus()->storedVersion)->toBe(runCodeVersion())
        ->and(runStatus()->owesWork())->toBeFalse();
});

it('never applies another package migrations', function () {
    registerRunPackage();
    registerHistoryPackage();

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(Schema::hasTable('run_widgets'))->toBeTrue()
        ->and(Schema::hasTable('history_widgets'))->toBeFalse()
        ->and(appliedMigrations())
        ->not->toContain('2026_01_01_000000_create_history_widgets_table')
        ->and(historyStatus()->storedVersion)->toBeNull();
});

it('advances the stored version to the code version rather than to the newest pending release', function () {
    registerRunPackage();

    expect(runStatus()->pendingVersions)->toBe(['0.0.1', '0.0.2', '0.0.3', '0.0.4']);

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(runStatus()->storedVersion)->toBe(runCodeVersion())
        ->and(runStatus()->storedVersion)->not->toBe('0.0.4');
});

it('reports the package as owing nothing after a successful run', function () {
    registerRunPackage();

    PluginUpdates::run(INSTALLED_PACKAGE);

    $status = runStatus();

    expect($status->owesWork())->toBeFalse()
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->versionsBehind())->toBe(0)
        ->and(PluginUpdates::report()->owing())->toBe([]);
});

it('runs the seeder once however many pending releases asked for it', function () {
    // 0.0.2 and 0.0.3 both ask. Asserted by the rows the seeder wrote, not by
    // counting calls, because a run that seeded twice is only ever visible in
    // the data an operator would be looking at.
    registerRunPackage();

    expect(runStatus()->seedingVersions)->toBe(['0.0.2', '0.0.3']);

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(seededRows())->toBe(1);
});

it('runs no seeder when no pending release owes one', function () {
    placeRunAt('0.0.3');
    registerRunPackage();

    expect(runStatus()->pendingVersions)->toBe(['0.0.4'])
        ->and(runStatus()->seedOwed())->toBeFalse();

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(seededRows())->toBe(0)
        ->and(runStatus()->storedVersion)->toBe(runCodeVersion());
});

it('updates a package that declared no seeder', function () {
    placeRunAt('0.0.3');
    registerRunPackage(withSeeder: false);

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(Schema::hasColumn('run_widgets', 'colour'))->toBeTrue()
        ->and(runStatus()->storedVersion)->toBe(runCodeVersion())
        ->and(runStatus()->owesWork())->toBeFalse();
});

it('updates a package that declared no migration path and owes only a seed', function () {
    // The schema this package's seeder writes into arrived some other way; all
    // it declares is a manifest and a seeder, and a run has to be fine with it.
    applyRunThrough('0.0.4');
    registerRunPackage(withMigrations: false);

    expect(runStatus()->schemaOwed())->toBeFalse()
        ->and(runStatus()->seedOwed())->toBeTrue();

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(seededRows())->toBe(1)
        ->and(runStatus()->storedVersion)->toBe(runCodeVersion());
});

it('creates the migration ledger when the database has never had one', function () {
    // A fresh edge host with no shell: the first thing that ever runs a
    // migration there is this, and Laravel's own ledger has to be made first.
    registerRunPackage();
    Schema::drop('migrations');

    PluginUpdates::run(INSTALLED_PACKAGE);

    expect(appliedMigrations())->toHaveCount(4)
        ->and(runStatus()->owesWork())->toBeFalse();
});

it('refuses to run a package whose deployed version Composer does not know', function () {
    // There is no version to advance the database to, and recording an invented
    // one would report a stale database as current for ever after.
    registerHistoryPackage();

    expect(fn () => PluginUpdates::run(HISTORY_PACKAGE))
        ->toThrow(UnrunnablePackage::class, HISTORY_PACKAGE);

    expect(Schema::hasTable('history_widgets'))->toBeFalse()
        ->and(historyStatus()->storedVersion)->toBeNull();
});

it('refuses to run a package whose manifest it cannot read', function () {
    // Whether a seed is owed is unknown, and running blind could silently skip
    // work a release owes.
    registerBrokenPackage(name: INSTALLED_PACKAGE);

    expect(fn () => PluginUpdates::run(INSTALLED_PACKAGE))
        ->toThrow(UnrunnablePackage::class);

    expect(PluginUpdates::report()->status(INSTALLED_PACKAGE)->storedVersion)->toBeNull();
});

it('refuses to run a package that never registered', function () {
    expect(fn () => PluginUpdates::run('qcentic-edge/never-registered'))
        ->toThrow(UnrunnablePackage::class);
});

it('refuses to run a package that owes a seed and declared no seeder', function () {
    // The library refuses to guess. A release that asks for a seed from a
    // package with no seeder is a mis-declaration, and skipping it quietly is
    // the silent-stale-database failure the whole design exists to prevent.
    registerRunPackage(withSeeder: false);

    expect(runStatus()->seedOwed())->toBeTrue();

    expect(fn () => PluginUpdates::run(INSTALLED_PACKAGE))
        ->toThrow(UnrunnablePackage::class);

    expect(Schema::hasTable('run_widgets'))->toBeFalse()
        ->and(runStatus()->storedVersion)->toBeNull();
});
