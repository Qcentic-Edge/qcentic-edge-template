<?php

use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

/**
 * Whether a package owes schema work is answered by diffing the migration
 * files in that package's own path against Laravel's `migrations` ledger, and
 * by nothing else. The manifest has no say in it, and neither does the stored
 * version — which is what lets an already-deployed site with no stored version
 * still report as up to date.
 */
it('owes schema work while a file in its own path is unapplied', function () {
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->schemaOwed())->toBeTrue()
        ->and($status->pendingMigrations)->toBe([
            '2026_01_01_000000_create_history_widgets_table',
            '2026_02_01_000000_create_history_notes_table',
            '2026_05_01_000000_add_colour_to_history_widgets_table',
            '2026_05_01_000001_create_history_tags_table',
        ]);
});

it('owes no schema work once every file in its path is applied', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');

    expect(historyStatus()->schemaOwed())->toBeFalse()
        ->and(historyStatus()->pendingMigrations)->toBe([]);
});

it('skips the files already in the migration ledger', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.2.0');

    expect(historyStatus()->pendingMigrations)->toBe([
        '2026_05_01_000000_add_colour_to_history_widgets_table',
        '2026_05_01_000001_create_history_tags_table',
    ]);
});

it('reads schema state from the migrator and never from the stored version', function () {
    // The case an existing deployed site is in the first time it upgrades:
    // every migration already applied, nothing recorded in the version ledger.
    // It must report as up to date on the schema axis.
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');

    $status = historyStatus();

    expect($status->installedVersion)->toBeNull()
        ->and($status->versionsBehind())->toBe(5)
        ->and($status->schemaOwed())->toBeFalse();
});

it('is unaffected by another package with unapplied migrations of its own', function () {
    registerHistoryPackage();
    registerFixturePackage();

    applyHistoryThrough('0.5.0');

    expect(historyStatus()->schemaOwed())->toBeFalse()
        ->and(PluginUpdates::report()->status('qcentic-edge/fixture-plugin')->schemaOwed())
        ->toBeTrue();
});

it('does not claim another package\'s applied migrations as its own', function () {
    registerHistoryPackage();
    registerFixturePackage();

    applyFixtureMigration(fixturePackagePath('migrations/2026_01_01_000000_create_fixture_widgets_table.php'));

    expect(PluginUpdates::report()->status('qcentic-edge/fixture-plugin')->schemaOwed())->toBeFalse()
        ->and(historyStatus()->pendingMigrations)->toHaveCount(4);
});

it('never owes schema work when the package declared no migration path', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-pathless')
            ->title('Fixture Pathless')
            ->manifest(historyPackagePath('updates.php')),
    );

    $status = PluginUpdates::report()->status('qcentic-edge/fixture-pathless');

    expect($status->pendingMigrations)->toBe([])
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->versionsBehind())->toBe(5);
});

it('owes no schema work when the declared path holds no migration files', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-empty-path')
            ->title('Fixture Empty Path')
            ->manifest(historyPackagePath('updates.php'))
            ->migrations(historyPackagePath('nowhere')),
    );

    expect(PluginUpdates::report()->status('qcentic-edge/fixture-empty-path')->schemaOwed())
        ->toBeFalse();
});

it('surfaces schema work that no release declared', function () {
    // The database is at the newest release the manifest lists, so there is no
    // version gap at all — and a file in the package's path is still unapplied.
    // Story 42: undeclared schema work surfaces rather than being skipped, so a
    // release nobody added a manifest row for can never report a stale database
    // as healthy.
    applyHistoryThrough('0.2.0');
    PluginUpdates::ledger()->record(HISTORY_PACKAGE, '0.5.0');
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->pendingVersions)->toBe([])
        ->and($status->versionsBehind())->toBe(0)
        ->and($status->schemaOwed())->toBeTrue()
        ->and($status->owesWork())->toBeTrue();
});
