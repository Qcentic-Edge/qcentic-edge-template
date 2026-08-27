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
        ->and($status->storedVersion)->toBe('0.1.0')
        ->and($status->codeVersion)->toBeNull()
        ->and($status->versionsBehind())->toBe(4)
        ->and($status->schemaOwed())->toBeTrue()
        ->and($status->seedOwed())->toBeTrue()
        ->and($status->owesWork())->toBeTrue()
        ->and($status->isBroken())->toBeFalse()
        ->and(array_map(fn ($table) => $table->name, $status->tables()))
        ->toBe(['history_widgets', 'history_notes', 'history_tags'])
        ->and($status->tables()[0]->rows)->toBe(2);
});

it('reports the deployed code version', function () {
    registerInstalledPackage();

    expect(PluginUpdates::report()->status(INSTALLED_PACKAGE)->codeVersion)
        ->toBe(libraryComposer()['version']);
});

it('says whether the deployed code version is known', function () {
    // The run advances the stored version to the code version, and there is
    // nothing to advance it to when Composer has never heard of the package.
    registerInstalledPackage();
    registerHistoryPackage();

    expect(PluginUpdates::report()->status(INSTALLED_PACKAGE)->codeVersionKnown())->toBeTrue()
        ->and(historyStatus()->codeVersion)->toBeNull()
        ->and(historyStatus()->codeVersionKnown())->toBeFalse();
});

it('owes nothing when the stored version is the code version and the path is fully applied', function () {
    // The installed-package fixture declares exactly one release, the version
    // this library's own composer.json carries — so stored, code and newest
    // release all land on the same point.
    registerInstalledPackage();

    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, libraryComposer()['version']);

    $status = PluginUpdates::report()->status(INSTALLED_PACKAGE);

    expect($status->storedVersion)->toBe($status->codeVersion)
        ->and($status->versionsBehind())->toBe(0)
        ->and($status->pendingMigrations)->toBe([])
        ->and($status->schemaOwed())->toBeFalse()
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->owesWork())->toBeFalse()
        ->and(PluginUpdates::report()->anythingOwed())->toBeFalse();
});

it('leaves a release the deployed code has not reached out of the pending list', function () {
    // The author added the manifest row before bumping composer.json, which is
    // the order the one-row developer checklist invites. The site's code cannot
    // run 99.0.0's work, so the site does not owe it — and a site that is level
    // with its code still owes nothing.
    registerInstalledPackage(installedPackagePath('ahead-of-code.php'));

    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, libraryComposer()['version']);

    $status = PluginUpdates::report()->status(INSTALLED_PACKAGE);

    expect($status->pendingVersions)->toBe([])
        ->and($status->versionsBehind())->toBe(0)
        ->and($status->seedOwed())->toBeFalse()
        ->and($status->owesWork())->toBeFalse()
        ->and(PluginUpdates::report()->anythingOwed())->toBeFalse();
});

it('leaves the pending list unbounded above when the code version is unknown', function () {
    // Composer has never heard of the history fixture, so there is no deployed
    // version to bound against and every release above the stored one is
    // pending — the upper bound filters against something or not at all.
    placeHistoryAt('0.3.0');
    registerHistoryPackage();

    expect(historyStatus()->codeVersion)->toBeNull()
        ->and(historyStatus()->pendingVersions)->toBe(['0.4.0', '0.5.0']);
});

it('counts the rows of every table a package declared', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');

    DB::table('history_widgets')->insert([['name' => 'one'], ['name' => 'two'], ['name' => 'three']]);
    DB::table('history_notes')->insert([['body' => 'a']]);

    $tables = collect(historyStatus()->tables())->keyBy(fn ($table) => $table->name);

    expect($tables['history_widgets']->rows)->toBe(3)
        ->and($tables['history_notes']->rows)->toBe(1)
        ->and($tables['history_tags']->rows)->toBe(0)
        ->and($tables['history_tags']->exists())->toBeTrue();
});

it('reports a declared table that does not exist yet as absent rather than throwing', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.1.0');

    $tables = collect(historyStatus()->tables())->keyBy(fn ($table) => $table->name);

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

    expect(PluginUpdates::report()->status('qcentic-edge/fixture-tableless')->tables())->toBe([]);
});

it('counts no rows to answer whether anything is owed', function () {
    // The badge is rendered on every page of the panel and asks only the cheap
    // question. Row counts are display-only and never an input to owesWork(),
    // so asking what is owed must not sweep a count over every declared table.
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');
    DB::table('history_widgets')->insert([['name' => 'one']]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect(PluginUpdates::report()->anythingOwed())->toBeTrue()
        ->and(countingQueries())->toBe([]);

    historyStatus()->tables();

    expect(countingQueries())->not->toBe([]);
});

it('counts a table once however many times its status is asked for the counts', function () {
    registerHistoryPackage();
    applyHistoryThrough('0.5.0');

    $status = historyStatus();
    $status->tables();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $status->tables();

    expect(countingQueries())->toBe([]);
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

    expect(historyStatus()->storedVersion)->toBeNull();
});

it('surfaces a package whose manifest is missing without blinding the report', function () {
    // Story 42: the misdeclared package is what surfaces, not the panel going
    // down. Every other package still reports normally beside it.
    registerHistoryPackage();
    placeHistoryAt('0.3.0');
    registerBrokenPackage();

    $status = PluginUpdates::report()->status(BROKEN_PACKAGE);

    expect($status->isBroken())->toBeTrue()
        ->and($status->problem)->toContain('nowhere.php')
        ->and($status->name)->toBe(BROKEN_PACKAGE)
        ->and($status->title)->toBe('Broken Plugin')
        ->and(historyStatus()->isBroken())->toBeFalse()
        ->and(historyStatus()->storedVersion)->toBe('0.3.0')
        ->and(historyStatus()->pendingVersions)->toBe(['0.4.0', '0.5.0']);
});

it('surfaces a package whose manifest is not a set of releases', function () {
    $path = sys_get_temp_dir().'/plugin-updates-report-bad-manifest.php';
    file_put_contents($path, '<?php return "not a manifest";');

    registerBrokenPackage($path);

    $status = PluginUpdates::report()->status(BROKEN_PACKAGE);

    expect($status->isBroken())->toBeTrue()
        ->and($status->problem)->toContain($path);

    unlink($path);
});

it('never reads a broken package as owing nothing', function () {
    registerBrokenPackage();

    $status = PluginUpdates::report()->status(BROKEN_PACKAGE);

    expect($status->owesWork())->toBeTrue()
        ->and(array_keys(PluginUpdates::report()->owing()))->toBe([BROKEN_PACKAGE])
        ->and(PluginUpdates::report()->anythingOwed())->toBeTrue();
});

it('reports no obligations it could not read for a broken package', function () {
    // What a package with an unreadable manifest owes is unknown, and unknown
    // is reported as owing attention rather than as a list of specifics the
    // library would be inventing.
    registerBrokenPackage();

    $status = PluginUpdates::report()->status(BROKEN_PACKAGE);

    expect($status->pendingVersions)->toBe([])
        ->and($status->versionsBehind())->toBe(0)
        ->and($status->seedingVersions)->toBe([])
        ->and($status->pendingMigrations)->toBe([])
        ->and($status->tables())->toBe([]);
});

it('reports a broken package alongside every package it can read', function () {
    registerHistoryPackage();
    registerBrokenPackage();
    registerFixturePackage();

    $all = PluginUpdates::report()->all();

    expect(array_keys($all))
        ->toBe([HISTORY_PACKAGE, BROKEN_PACKAGE, 'qcentic-edge/fixture-plugin'])
        ->and($all[BROKEN_PACKAGE]->isBroken())->toBeTrue()
        ->and($all[HISTORY_PACKAGE]->isBroken())->toBeFalse()
        ->and($all['qcentic-edge/fixture-plugin']->isBroken())->toBeFalse();
});

it('still knows which versions a broken package is between', function () {
    // The manifest is the only thing that could not be read. Where the database
    // is and what the code is are read from elsewhere, and they are what tells
    // the operator which package to go and look at.
    registerBrokenPackage(name: INSTALLED_PACKAGE);
    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, '0.0.1');

    $status = PluginUpdates::report()->status(INSTALLED_PACKAGE);

    expect($status->storedVersion)->toBe('0.0.1')
        ->and($status->codeVersion)->toBe(libraryComposer()['version']);
});

/**
 * Whether a run would be refused, answered before anything draws a button.
 *
 * The three cases are the runner's own, read from the report rather than
 * rediscovered at the call site: a renderer that asked the registry what a
 * package declared would be a second view of update state beside this one, and
 * a renderer that asked nothing would draw a button that throws on click.
 */
it('says a fully declared package could be run', function () {
    registerRunPackage();

    $status = runStatus();

    expect($status->owesWork())->toBeTrue()
        ->and($status->runnable())->toBeTrue()
        ->and($status->unrunnableReason())->toBeNull()
        ->and($status->needsAttention())->toBeFalse();
});

it('refuses to run a package whose manifest it cannot read, and says so', function () {
    registerBrokenPackage(name: INSTALLED_PACKAGE);

    $status = PluginUpdates::report()->status(INSTALLED_PACKAGE);

    expect($status->owesWork())->toBeTrue()
        ->and($status->runnable())->toBeFalse()
        ->and($status->needsAttention())->toBeTrue()
        ->and($status->unrunnableReason())->toContain('manifest cannot be read')
        ->and($status->unrunnableReason())->toContain('nowhere.php');
});

it('refuses to run a package whose deployed version Composer does not know, and says so', function () {
    // Composer has never heard of the history fixture, so there is no version
    // to advance its database to — and it owes schema work all the same.
    registerHistoryPackage();

    $status = historyStatus();

    expect($status->schemaOwed())->toBeTrue()
        ->and($status->owesWork())->toBeTrue()
        ->and($status->runnable())->toBeFalse()
        ->and($status->needsAttention())->toBeTrue()
        ->and($status->unrunnableReason())->toContain('what version of its code is deployed');
});

it('refuses to run a package that owes a seed and declared no seeder, and says which releases asked', function () {
    registerRunPackage(withSeeder: false);

    $status = runStatus();

    expect($status->seedOwed())->toBeTrue()
        ->and($status->runnable())->toBeFalse()
        ->and($status->needsAttention())->toBeTrue()
        ->and($status->unrunnableReason())->toContain('ask for a seed')
        ->and($status->unrunnableReason())->toContain($status->seedingVersions[0]);
});

it('tells owing nothing apart from owing work that cannot be run', function () {
    // Two packages a renderer must not confuse: one is quiet, the other has a
    // person to fetch. Neither gets a button, and only one of them is fine.
    registerInstalledPackage();
    applyHistoryThrough('0.5.0');
    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, libraryComposer()['version']);

    registerHistoryPackage();

    $quiet = PluginUpdates::report()->status(INSTALLED_PACKAGE);
    $stuck = historyStatus();

    expect($quiet->owesWork())->toBeFalse()
        ->and($quiet->needsAttention())->toBeFalse()
        ->and($quiet->runnable())->toBeTrue()
        ->and($stuck->owesWork())->toBeTrue()
        ->and($stuck->needsAttention())->toBeTrue();
});
