<?php

use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;
use QcenticEdge\PluginUpdates\Notice\UpdatesNotice;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Tests\Fixtures\RoleAwareUser;

/**
 * The library's own renderer, for a site with no installer plugin. Not a seam
 * of its own: the notice reads the report and reimplements none of it, so what
 * is asserted here is only that the topbar speaks when work is owed and stays
 * quiet when it is not.
 */
beforeEach(fn () => test()->actingAs(new User));

it('names a package that owes work, in the topbar of every page', function () {
    registerRunPackage();

    expect(renderTopbar())->toContain('Run Plugin');
});

it('says nothing when every package is up to date', function () {
    registerInstalledPackage();
    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, libraryComposer()['version']);

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
    placeRunAt('0.0.2');

    Livewire::test(UpdatesNotice::class)
        ->callAction('update', arguments: ['package' => INSTALLED_PACKAGE]);

    expect(runStatus()->storedVersion)->toBe(runCodeVersion())
        ->and(runStatus()->owesWork())->toBeFalse();
});

it('offers no button for a package that owes work it would refuse to run, and gives the reason instead', function () {
    // A broken or otherwise unrunnable package needs a person, not a click that
    // would be refused the moment it was made.
    registerRunPackage(withSeeder: false);

    expect(runStatus()->needsAttention())->toBeTrue();

    $notice = renderTopbar();

    expect($notice)->toContain('needs attention')
        ->toContain('declared no seeder');
});
