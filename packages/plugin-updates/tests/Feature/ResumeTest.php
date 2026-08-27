<?php

use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Tests\Fixtures\MidRunFailure;
use QcenticEdge\PluginUpdates\Tests\Fixtures\RunPackageSeeder;

/**
 * The guarantee that makes a long catch-up safe to attempt on a host with a
 * request timeout: failure is a retry, and a partial run is still progress.
 *
 * Laravel's `migrations` ledger records each file as it succeeds, so a run that
 * dies partway keeps everything applied up to that point. The stored version
 * advances only on full success, so the package still reports as behind and the
 * button stays — and the second attempt resumes from the first unapplied file
 * rather than starting over.
 *
 * The run fixture has four migrations and its third can be made to fail, which
 * is exactly the shape this needs.
 */
beforeEach(fn () => MidRunFailure::disarm());

it('keeps the two files applied before a failure on the third', function () {
    registerRunPackage();
    MidRunFailure::arm();

    expect(fn () => PluginUpdates::run(LIBRARY_PACKAGE))->toThrow(RuntimeException::class);

    expect(Schema::hasTable('run_widgets'))->toBeTrue()
        ->and(Schema::hasTable('run_notes'))->toBeTrue()
        ->and(Schema::hasTable('run_tags'))->toBeFalse()
        ->and(appliedMigrations())->toBe([
            '2026_01_01_000000_create_run_widgets_table',
            '2026_02_01_000000_create_run_notes_table',
        ]);
});

it('leaves the stored version unmoved when a run failed partway', function () {
    registerRunPackage();
    MidRunFailure::arm();

    expect(fn () => PluginUpdates::run(LIBRARY_PACKAGE))->toThrow(RuntimeException::class);

    expect(statusOf(LIBRARY_PACKAGE)->storedVersion)->toBeNull();
});

it('still reports the package as behind after a run failed partway', function () {
    registerRunPackage();
    MidRunFailure::arm();

    expect(fn () => PluginUpdates::run(LIBRARY_PACKAGE))->toThrow(RuntimeException::class);

    $status = statusOf(LIBRARY_PACKAGE);

    expect($status->owesWork())->toBeTrue()
        ->and($status->schemaOwed())->toBeTrue()
        ->and($status->versionsBehind())->toBe(4)
        ->and($status->pendingMigrations)->toBe([
            '2026_03_01_000000_create_run_tags_table',
            '2026_04_01_000000_add_colour_to_run_widgets_table',
        ]);
});

it('resumes from the first unapplied file on a second run and reaches the code version', function () {
    registerRunPackage();
    MidRunFailure::arm();

    expect(fn () => PluginUpdates::run(LIBRARY_PACKAGE))->toThrow(RuntimeException::class);

    MidRunFailure::disarm();

    PluginUpdates::run(LIBRARY_PACKAGE);

    // The first two files were not run a second time — re-running a create
    // table would have failed the second run outright — and every file is in
    // the ledger exactly once.
    expect(array_count_values(appliedMigrations()))->each->toBe(1)
        ->and(Schema::hasTable('run_tags'))->toBeTrue()
        ->and(Schema::hasColumn('run_widgets', 'colour'))->toBeTrue()
        ->and(statusOf(LIBRARY_PACKAGE)->storedVersion)->toBe(libraryVersion())
        ->and(statusOf(LIBRARY_PACKAGE)->owesWork())->toBeFalse();
});

it('seeds once across a failed run and the run that finished it', function () {
    // The seed is owed by releases the first attempt never got past, so it is
    // still owed on the second — and having been attempted twice must not show
    // up as two rows.
    registerRunPackage();
    MidRunFailure::arm();

    expect(fn () => PluginUpdates::run(LIBRARY_PACKAGE))->toThrow(RuntimeException::class);

    expect(Schema::hasTable(RunPackageSeeder::TABLE))->toBeTrue()
        ->and(seededRows())->toBe(0);

    MidRunFailure::disarm();

    PluginUpdates::run(LIBRARY_PACKAGE);

    expect(seededRows())->toBe(1);
});
