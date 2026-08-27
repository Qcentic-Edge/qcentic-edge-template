<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\PluginUpdates;

it('creates its table on first use', function () {
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse();

    versionLedger()->ensureTable();

    expect(Schema::hasTable(VersionLedger::TABLE))->toBeTrue();
});

it('creates its table on a first write that never asked for it', function () {
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse();

    versionLedger()->record('qcentic-edge/fixture-plugin', '0.3.0');

    expect(Schema::hasTable(VersionLedger::TABLE))->toBeTrue();
});

it('does not recreate the table on a second call', function () {
    $ledger = versionLedger();

    $ledger->ensureTable();
    $ledger->record('qcentic-edge/fixture-plugin', '0.3.0');

    $ledger->ensureTable();

    expect($ledger->storedVersion('qcentic-edge/fixture-plugin'))->toBe('0.3.0');
});

it('ships no migration file for its own table', function () {
    expect(glob(libraryPath('database/migrations').'/*.php') ?: [])->toBe([])
        ->and(is_dir(libraryPath('database')))->toBeFalse();
});

it('writes a stored version and reads it back', function () {
    versionLedger()->record('qcentic-edge/fixture-plugin', '0.4.0');

    expect(versionLedger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBe('0.4.0');
});

it('reads back a package with no stored version as absent', function () {
    versionLedger()->record('qcentic-edge/fixture-other', '0.4.0');

    expect(versionLedger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBeNull();
});

it('reads back as absent on a database that has never seen the ledger', function () {
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse()
        ->and(versionLedger()->storedVersion('qcentic-edge/fixture-plugin'))
        ->toBeNull();
});

it('creates nothing when asked what version a package is at', function () {
    // Reading is a read. Several replicas serve the panel and one of them may
    // be on a read-only connection, where DDL on a read path throws where a
    // null would have done.
    versionLedger()->storedVersion('qcentic-edge/fixture-plugin');

    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse();
});

it('advances a stored version rather than adding a second row', function () {
    $ledger = versionLedger();

    $ledger->record('qcentic-edge/fixture-plugin', '0.4.0');
    $ledger->record('qcentic-edge/fixture-plugin', '0.10.0');

    expect($ledger->storedVersion('qcentic-edge/fixture-plugin'))->toBe('0.10.0')
        ->and(DB::table(VersionLedger::TABLE)->count())->toBe(1);
});

it('leaves created_at alone when a stored version advances', function () {
    $ledger = versionLedger();

    $ledger->record('qcentic-edge/fixture-plugin', '0.4.0');

    $first = DB::table(VersionLedger::TABLE)
        ->where('package', 'qcentic-edge/fixture-plugin')
        ->first();

    $this->travel(1)->minutes();

    $ledger->record('qcentic-edge/fixture-plugin', '0.5.0');

    $second = DB::table(VersionLedger::TABLE)
        ->where('package', 'qcentic-edge/fixture-plugin')
        ->first();

    expect($first->created_at)->not->toBeNull()
        ->and($second->created_at)->toBe($first->created_at)
        ->and($second->updated_at)->not->toBe($first->updated_at)
        ->and($second->version)->toBe('0.5.0');
});

it('keeps each package on its own row', function () {
    $ledger = versionLedger();

    $ledger->record('qcentic-edge/fixture-one', '0.1.0');
    $ledger->record('qcentic-edge/fixture-two', '0.2.0');

    expect($ledger->storedVersion('qcentic-edge/fixture-one'))->toBe('0.1.0')
        ->and($ledger->storedVersion('qcentic-edge/fixture-two'))->toBe('0.2.0');
});
