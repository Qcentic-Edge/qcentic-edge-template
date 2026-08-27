<?php

use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;
use QcenticEdge\PluginUpdates\Notice\UpdatesNotice;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Report\UpdateReport;
use QcenticEdge\PluginUpdates\Tests\Fixtures\RoleAwareUser;

/**
 * The library's own renderer, for a site with no installer plugin. Not a seam
 * of its own: the notice reads the report and reimplements none of it, so what
 * is asserted here is only that the topbar speaks when work is owed, stays
 * quiet when it is not, and — when the report cannot be read at all — says so
 * rather than taking the panel down with it.
 */
beforeEach(fn () => test()->actingAs(new User));

const REPORT_FAILURE = 'SQLSTATE[HY000] [2002] Connection refused';

/**
 * Stand in for a database that has gone away mid-request: every route to the
 * report now throws, whichever of its reads the caller wanted.
 */
function breakTheReport(): void
{
    app()->bind(UpdateReport::class, function (): never {
        throw new RuntimeException(REPORT_FAILURE);
    });
}

it('names a package that owes work, in the topbar of every page', function () {
    registerRunPackage();

    expect(renderTopbar())->toContain('Run Plugin');
});

it('says nothing when every package is up to date', function () {
    registerInstalledPackage();
    applyThrough(HISTORY_FIXTURE, '0.5.0');
    versionLedger()->record(LIBRARY_PACKAGE, libraryVersion());

    expect(PluginUpdates::report()->anythingOwed())->toBeFalse()
        ->and(renderTopbar())->toBe('');
});

it('surfaces every package that owes work, without one masking another', function () {
    registerRunPackage();
    registerHistoryPackage();
    registerBrokenPackage();

    $notice = renderTopbar();

    expect($notice)->toContain('Run Plugin')
        ->toContain('History Plugin')
        ->toContain('Broken Plugin');
});

it('steps aside when the installer plugin is present, leaving one updates surface', function () {
    registerRunPackage();
    installerPluginPresent();

    expect(PluginUpdates::report()->anythingOwed())->toBeTrue()
        ->and(renderTopbar())->toBe('');
});

it('shows nothing to a guest, or to a user who is not a super admin', function () {
    registerRunPackage();

    auth()->logout();
    expect(renderTopbar())->toBe('');

    test()->actingAs(new RoleAwareUser(isSuperAdmin: false));
    expect(renderTopbar())->toBe('');

    test()->actingAs(new RoleAwareUser(isSuperAdmin: true));
    expect(renderTopbar())->toContain('Run Plugin');
});

it('carries the action that brings a package up to date', function () {
    registerRunPackage();
    placeAt(RUN_FIXTURE, '0.0.2');

    Livewire::test(UpdatesNotice::class)
        ->callAction('update', arguments: ['package' => LIBRARY_PACKAGE]);

    expect(statusOf(LIBRARY_PACKAGE)->storedVersion)->toBe(libraryVersion())
        ->and(statusOf(LIBRARY_PACKAGE)->owesWork())->toBeFalse();
});

it('offers no button for a package that owes work it would refuse to run, and gives the reason instead', function () {
    // A broken or otherwise unrunnable package needs a person, not a click that
    // would be refused the moment it was made.
    registerRunPackage(withSeeder: false);

    expect(statusOf(LIBRARY_PACKAGE)->needsAttention())->toBeTrue();

    $notice = renderTopbar();

    expect($notice)->toContain('needs attention')
        ->toContain('declared no seeder');
});

/**
 * A database blip costs the notice, never the panel.
 *
 * This hook renders into the topbar of *every* page of a panel, and building
 * the report touches the database before it has said anything — the version
 * ledger asks whether its table exists, the pending-migration diff asks the
 * migrator whether its repository does. An unguarded read here does not lose a
 * badge, it returns a 500 for every screen in the panel — and it does so on
 * exactly the sites this notice exists for, the ones with no installer and no
 * other updates surface to fall back on.
 *
 * The failure is injected at the report rather than by pulling the database out
 * from under the request, because where in the read it came from is exactly
 * what a renderer is not allowed to care about. Both readers are exercised: the
 * hook decides whether to render at all, and the component renders the list.
 */
it('leaves the panel rendering when the report cannot be read, and says so', function () {
    registerRunPackage();

    expect(PluginUpdates::report()->anythingOwed())->toBeTrue();

    breakTheReport();

    $notice = renderTopbar();

    expect($notice)->toContain('Update status is unavailable')
        ->toContain(REPORT_FAILURE)
        ->not->toContain('Run Plugin');

    Livewire::test(UpdatesNotice::class)->assertOk();
});

it('says nothing to a non-operator even when the report cannot be read', function () {
    // The guard is not a way around the gate: a user who may not see update
    // state may not see that it could not be read either.
    registerRunPackage();
    breakTheReport();

    auth()->logout();

    expect(renderTopbar())->toBe('');
});
