<?php

use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

/**
 * The case exercised least in development and most in the field: a site
 * upgraded after a quiet six months, several releases behind in one jump.
 *
 * The history fixture spans five releases and four migration files, so each
 * test here places the database where a site that last deployed at some
 * earlier release would be, and asks the report what it owes.
 */
it('reports both the gap and both unapplied files two versions behind', function () {
    placeHistoryAt('0.3.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->installedVersion)->toBe('0.3.0')
        ->and($status->pendingVersions)->toBe(['0.4.0', '0.5.0'])
        ->and($status->versionsBehind())->toBe(2)
        ->and($status->pendingMigrations)->toBe([
            '2026_05_01_000000_add_colour_to_history_widgets_table',
            '2026_05_01_000001_create_history_tags_table',
        ])
        ->and($status->schemaOwed())->toBeTrue();
});

it('reports exactly the unapplied files four versions behind, skipping those in the ledger', function () {
    // Four releases pending. Only the first and the last of them added schema;
    // 0.3.0 and 0.4.0 added none. The file 0.1.0 shipped is already applied and
    // must not be reported.
    placeHistoryAt('0.1.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->pendingVersions)->toBe(['0.2.0', '0.3.0', '0.4.0', '0.5.0'])
        ->and($status->versionsBehind())->toBe(4)
        ->and($status->pendingMigrations)->toBe([
            '2026_02_01_000000_create_history_notes_table',
            '2026_05_01_000000_add_colour_to_history_widgets_table',
            '2026_05_01_000001_create_history_tags_table',
        ])
        ->and($status->pendingMigrations)
        ->not->toContain('2026_01_01_000000_create_history_widgets_table');
});

it('owes a seed asked for by a skipped middle release even though the newest declines', function () {
    // 0.3.0 owes a seed; 0.4.0 and 0.5.0 do not. Reading only the newest entry
    // is the bug this design exists to prevent.
    placeHistoryAt('0.1.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->seedingVersions)->toBe(['0.3.0'])
        ->and($status->seedOwed())->toBeTrue();
});

it('owes no seed across a gap that contains no seed obligation', function () {
    placeHistoryAt('0.3.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->pendingVersions)->toBe(['0.4.0', '0.5.0'])
        ->and($status->seedingVersions)->toBe([])
        ->and($status->seedOwed())->toBeFalse();
});

it('owes nothing across a version gap when the path is applied and no release wants a seed', function () {
    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record(HISTORY_PACKAGE, '0.3.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->versionsBehind())->toBe(2)
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->owesWork())->toBeFalse()
        ->and($status->owesNothing())->toBeTrue()
        ->and(PluginUpdates::report()->owing())->toBe([]);
});

it('treats a package with no stored version as being at the oldest release', function () {
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->installedVersion)->toBeNull()
        ->and($status->pendingVersions)->toBe(['0.1.0', '0.2.0', '0.3.0', '0.4.0', '0.5.0'])
        ->and($status->versionsBehind())->toBe(5)
        ->and($status->seedOwed())->toBeTrue();
});

it('sorts a manifest listed out of order by version rather than by string', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/out-of-order-plugin')
            ->title('Out Of Order Plugin')
            ->manifest(outOfOrderPackagePath('updates.php')),
    );

    $status = PluginUpdates::report()->status('qcentic-edge/out-of-order-plugin');

    expect($status->pendingVersions)->toBe(['0.1.0', '0.2.0', '0.9.0', '0.10.0'])
        ->and($status->versionsBehind())->toBe(4);
});

it('counts 0.10.0 as above 0.9.0 rather than below it', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/out-of-order-plugin')
            ->title('Out Of Order Plugin')
            ->manifest(outOfOrderPackagePath('updates.php')),
    );
    PluginUpdates::ledger()->record('qcentic-edge/out-of-order-plugin', '0.9.0');

    $status = PluginUpdates::report()->status('qcentic-edge/out-of-order-plugin');

    expect($status->pendingVersions)->toBe(['0.10.0'])
        ->and($status->versionsBehind())->toBe(1)
        ->and($status->seedOwed())->toBeFalse();
});

it('collapses a multi-version gap into a single reported row', function () {
    // Story 33: one row and one click regardless of how many releases were
    // skipped, not one row per pending version.
    placeHistoryAt('0.1.0');
    registerHistoryPackage();

    expect(PluginUpdates::report()->owing())->toHaveCount(1)
        ->and(PluginUpdates::report()->owing()[HISTORY_PACKAGE]->versionsBehind())->toBe(4);
});
