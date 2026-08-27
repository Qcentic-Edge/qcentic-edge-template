<?php

use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Tests\Fixtures\BootRegisteringProvider;

/**
 * Nothing runs on boot. Several replicas starting the same schema change on
 * deploy is the failure that avoids, and an operator who redeploys must find
 * their schema exactly as they left it until they click.
 *
 * There are two ways that could go wrong, and a test for each: the library's
 * own provider doing something as the application comes up, and a package's
 * provider doing something as it declares itself. `BootRegisteringProvider` is
 * a package declaring itself exactly as a real plugin does, and registering it
 * on a running application boots it there and then.
 */
it('touches nothing as the library provider boots', function () {
    // The library's provider has already booted by the time this body runs —
    // that is what the test application does — so the state of the database
    // here is the state it left behind.
    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse()
        ->and(appliedMigrations())->toBe([]);
});

it('runs nothing when a package declares itself from its own provider', function () {
    $this->app->register(BootRegisteringProvider::class);

    expect(PluginUpdates::packages())->toHaveKey(INSTALLED_PACKAGE)
        ->and(runStatus()->owesWork())->toBeTrue()
        ->and(Schema::hasTable('run_widgets'))->toBeFalse()
        ->and(Schema::hasTable('run_notes'))->toBeFalse()
        ->and(appliedMigrations())->toBe([]);
});

it('writes no version to the ledger while a package declares itself', function () {
    // The ledger table is created on its first write, so its absence is proof
    // that declaring a package wrote nothing at all.
    $this->app->register(BootRegisteringProvider::class);

    expect(Schema::hasTable(VersionLedger::TABLE))->toBeFalse()
        ->and(runStatus()->storedVersion)->toBeNull();
});
