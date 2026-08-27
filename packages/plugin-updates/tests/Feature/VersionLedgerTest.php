<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\PluginUpdates;

it('creates its table on first use', function () {
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse();

    PluginUpdates::ledger()->ensureTable();

    expect(Schema::hasTable(VersionLedger::TABLE))->toBeTrue();
});

it('does not recreate the table on a second call', function () {
    $ledger = PluginUpdates::ledger();

    $ledger->ensureTable();
    $ledger->record('qcentic-edge/fixture-plugin', '0.3.0');

    $ledger->ensureTable();

    expect($ledger->storedVersion('qcentic-edge/fixture-plugin'))->toBe('0.3.0');
});

it('ships no migration file for its own table', function () {
    expect(glob(__DIR__.'/../../database/migrations/*.php') ?: [])->toBe([])
        ->and(is_dir(__DIR__.'/../../database'))->toBeFalse();
});

it('writes a stored version and reads it back', function () {
    PluginUpdates::ledger()->record('qcentic-edge/fixture-plugin', '0.4.0');

    expect(PluginUpdates::ledger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBe('0.4.0');
});

it('reads back a package with no stored version as absent', function () {
    PluginUpdates::ledger()->record('qcentic-edge/fixture-other', '0.4.0');

    expect(PluginUpdates::ledger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBeNull();
});

it('reads back as absent on a database that has never seen the ledger', function () {
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse()
        ->and(PluginUpdates::ledger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBeNull();
});

it('advances a stored version rather than adding a second row', function () {
    $ledger = PluginUpdates::ledger();

    $ledger->record('qcentic-edge/fixture-plugin', '0.4.0');
    $ledger->record('qcentic-edge/fixture-plugin', '0.10.0');

    expect($ledger->storedVersion('qcentic-edge/fixture-plugin'))->toBe('0.10.0')
        ->and(DB::table(VersionLedger::TABLE)->count())->toBe(1);
});

it('keeps each package on its own row', function () {
    $ledger = PluginUpdates::ledger();

    $ledger->record('qcentic-edge/fixture-one', '0.1.0');
    $ledger->record('qcentic-edge/fixture-two', '0.2.0');

    expect($ledger->storedVersion('qcentic-edge/fixture-one'))->toBe('0.1.0')
        ->and($ledger->storedVersion('qcentic-edge/fixture-two'))->toBe('0.2.0');
});
