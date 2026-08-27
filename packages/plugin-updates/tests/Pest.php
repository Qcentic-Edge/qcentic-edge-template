<?php

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;
use QcenticEdge\PluginUpdates\Tests\Fixtures\InstallerLikePlugin;
use QcenticEdge\PluginUpdates\Tests\Fixtures\RunPackageSeeder;
use QcenticEdge\PluginUpdates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

// Host/ is bound nowhere here on purpose: each file in it boots a different
// shape of host — one with no Livewire and no Filament, one with Livewire and
// no Filament, one where the library's own provider never registered — so each
// names its own base class at the top of the file rather than sharing a
// directory's.

/**
 * The library's own Composer name, and the reason two fixtures share it.
 *
 * It is the one package guaranteed to be installed while this suite runs, so a
 * fixture that has to have a code version Composer can resolve — because a run
 * refuses a package whose deployed version it cannot — registers under this
 * name and borrows the library's own. It names a Composer identity, not a
 * fixture: which fixture is registered under it is whichever `register…()`
 * helper the test called.
 */
const LIBRARY_PACKAGE = 'qcentic-edge/plugin-updates';

const HISTORY_PACKAGE = 'qcentic-edge/history-plugin';
const OUT_OF_ORDER_PACKAGE = 'qcentic-edge/out-of-order-plugin';
const BROKEN_PACKAGE = 'qcentic-edge/broken-plugin';

/** The two fixtures with a release history behind them. See `releaseFixture()`. */
const HISTORY_FIXTURE = 'history';

const RUN_FIXTURE = 'run';

function fixturePackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/FixturePackage/'.$path, '/');
}

/**
 * Declare the fixture package the way a plugin's own service provider does:
 * one call, package name, title and manifest, plus the optional migration
 * path, seeder and tables.
 */
function registerFixturePackage(string $name = 'qcentic-edge/fixture-plugin'): UpdatablePackage
{
    $package = UpdatablePackage::make($name)
        ->title('Fixture Plugin')
        ->manifest(fixturePackagePath('updates.php'))
        ->migrations(fixturePackagePath('migrations'))
        ->seeder(FixtureSeeder::class)
        ->tables(['fixture_widgets']);

    PluginUpdates::register($package);

    return $package;
}

function historyPackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/HistoryPackage/'.$path, '/');
}

function runPackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/RunPackage/'.$path, '/');
}

function outOfOrderPackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/OutOfOrderPackage/'.$path, '/');
}

function installedPackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/InstalledPackage/'.$path, '/');
}

/**
 * The history fixture: a package with five releases and four migration files,
 * so a test can place a database at any point in that history and ask the
 * report what the site owes. The multi-version path is the one exercised least
 * in development and most in the field, so it is the one that gets a fixture of
 * its own.
 */
function registerHistoryPackage(): UpdatablePackage
{
    $package = UpdatablePackage::make(HISTORY_PACKAGE)
        ->title('History Plugin')
        ->manifest(historyPackagePath('updates.php'))
        ->migrations(historyPackagePath('migrations'))
        ->seeder(FixtureSeeder::class)
        ->tables(['history_widgets', 'history_notes', 'history_tags']);

    PluginUpdates::register($package);

    return $package;
}

/**
 * The fixture the run tests drive: four releases, four migration files, a
 * seeder, and a third migration a test can make fail.
 *
 * Its releases all sit below LIBRARY_PACKAGE's version, and none of them equals
 * it, so a test can tell "advanced to the code version" apart from "advanced to
 * the newest pending release".
 *
 * Pass `withMigrations: false` for a package that owes only a seed, and
 * `withSeeder: false` for one that declares none.
 */
function registerRunPackage(bool $withMigrations = true, bool $withSeeder = true): UpdatablePackage
{
    $package = UpdatablePackage::make(LIBRARY_PACKAGE)
        ->title('Run Plugin')
        ->manifest(runPackagePath('updates.php'))
        ->tables(['run_widgets', 'run_notes', 'run_tags']);

    if ($withMigrations) {
        $package->migrations(runPackagePath('migrations'));
    }

    if ($withSeeder) {
        $package->seeder(RunPackageSeeder::class);
    }

    PluginUpdates::register($package);

    return $package;
}

/**
 * A package with one release, pinned to the deployed version of
 * LIBRARY_PACKAGE, so a test can put the stored version, the code version and
 * the newest release all at the same point — the site that owes nothing.
 *
 * Not the run fixture with a different manifest: this one exists to be quiet,
 * and its migrations are the history fixture's so that "fully applied" is a
 * state a test can arrange with `applyThrough(HISTORY_FIXTURE, …)`.
 */
function registerInstalledPackage(?string $manifest = null): UpdatablePackage
{
    $package = UpdatablePackage::make(LIBRARY_PACKAGE)
        ->title('Plugin Updates')
        ->manifest($manifest ?? installedPackagePath('updates.php'))
        ->migrations(historyPackagePath('migrations'));

    PluginUpdates::register($package);

    return $package;
}

/**
 * A package whose manifest lists its releases in no particular order and spans
 * the version whose string ordering disagrees with its version ordering.
 */
function registerOutOfOrderPackage(): UpdatablePackage
{
    $package = UpdatablePackage::make(OUT_OF_ORDER_PACKAGE)
        ->title('Out Of Order Plugin')
        ->manifest(outOfOrderPackagePath('updates.php'));

    PluginUpdates::register($package);

    return $package;
}

/**
 * A package that declared a manifest the library cannot read. Defaults to a
 * path with no file at it; pass one to a file that is not a set of releases to
 * get the other half of the failure.
 */
function registerBrokenPackage(?string $manifest = null, string $name = BROKEN_PACKAGE): UpdatablePackage
{
    $package = UpdatablePackage::make($name)
        ->title('Broken Plugin')
        ->manifest($manifest ?? historyPackagePath('nowhere.php'));

    PluginUpdates::register($package);

    return $package;
}

/**
 * The two fixtures that span several releases: which migration files each of
 * their releases shipped, where those files live, and the name the fixture is
 * registered under.
 *
 * There are two of them because they answer questions that cannot be answered
 * by one package. HistoryPackage is registered under a name Composer has never
 * heard of, which is exactly what a reporting test wants: a database placed at
 * any point in a history, asked what it owes. RunPackage is registered under
 * LIBRARY_PACKAGE, because a run refuses a package whose deployed version
 * Composer cannot resolve, and a run test has to get past that refusal. One
 * fixture cannot be both unknown to Composer and known to it, so their shapes
 * rhyme and their purposes do not.
 *
 * The release-to-file map lives in the test suite and must never grow anywhere
 * in `src/`. The library reads schema state from the migrator's own per-file
 * ledger precisely so that it never needs to know which release a file belongs
 * to; a copy of that fact inside the library would drift the same way the
 * rejected manifest schema flag would have.
 *
 * @return array{package: string, migrations: string, releases: array<string, list<string>>}
 */
function releaseFixture(string $fixture): array
{
    return match ($fixture) {
        HISTORY_FIXTURE => [
            'package' => HISTORY_PACKAGE,
            'migrations' => historyPackagePath('migrations'),
            'releases' => [
                '0.1.0' => ['2026_01_01_000000_create_history_widgets_table'],
                '0.2.0' => ['2026_02_01_000000_create_history_notes_table'],
                '0.3.0' => [],
                '0.4.0' => [],
                '0.5.0' => [
                    '2026_05_01_000000_add_colour_to_history_widgets_table',
                    '2026_05_01_000001_create_history_tags_table',
                ],
            ],
        ],
        RUN_FIXTURE => [
            'package' => LIBRARY_PACKAGE,
            'migrations' => runPackagePath('migrations'),
            'releases' => [
                '0.0.1' => ['2026_01_01_000000_create_run_widgets_table'],
                '0.0.2' => ['2026_02_01_000000_create_run_notes_table'],
                '0.0.3' => ['2026_03_01_000000_create_run_tags_table'],
                '0.0.4' => ['2026_04_01_000000_add_colour_to_run_widgets_table'],
            ],
        ],
    };
}

/**
 * Run every migration this fixture shipped at or before the given release, and
 * record each in Laravel's own ledger — which is what an operator's database
 * looks like when their site last deployed at that release.
 */
function applyThrough(string $fixture, string $release): void
{
    $fixture = releaseFixture($fixture);

    foreach ($fixture['releases'] as $version => $migrations) {
        if (version_compare($version, $release, '>')) {
            continue;
        }

        foreach ($migrations as $migration) {
            applyFixtureMigration($fixture['migrations'].'/'.$migration.'.php');
        }
    }
}

/** Apply one migration file and add it to Laravel's `migrations` ledger. */
function applyFixtureMigration(string $file): void
{
    (require $file)->up();

    DB::table('migrations')->insert([
        'migration' => basename($file, '.php'),
        'batch' => 1,
    ]);
}

/**
 * Put the database where a site that last deployed this fixture at this release
 * would be: its migrations applied and its stored version recorded.
 */
function placeAt(string $fixture, string $release): void
{
    applyThrough($fixture, $release);

    versionLedger()->record(releaseFixture($fixture)['package'], $release);
}

/** What the report says one package owes — read back the same way anything else reads it. */
function statusOf(string $package): PackageStatus
{
    return PluginUpdates::report()->status($package)
        ?? throw new RuntimeException("No package [{$package}] is registered; a test that asks what "
            .'it owes has to register it first.');
}

/**
 * The version ledger itself.
 *
 * Deliberately not reachable from `PluginUpdates`: outside the library, update
 * state is read through the report and written only by `run()`, and a public
 * accessor beside those would be a second view of it. Placing a database at a
 * point in history is not something a consumer can do, or should be able to —
 * so the suite reaches for the library's own class to arrange a fixture, which
 * is the one liberty a test takes that a consumer may not.
 */
function versionLedger(): VersionLedger
{
    return app(VersionLedger::class);
}

/** Every migration Laravel's own ledger records as applied. */
function appliedMigrations(): array
{
    return DB::table('migrations')->orderBy('id')->pluck('migration')->all();
}

/** How many rows the run fixture's seeder has written. One per time it ran. */
function seededRows(): int
{
    return DB::table(RunPackageSeeder::TABLE)->count();
}

/**
 * The row-count queries logged since the query log was enabled, so a test can
 * assert what the cheap question does not pay for.
 *
 * @return list<array<string, mixed>>
 */
function countingQueries(): array
{
    return array_values(array_filter(
        DB::getQueryLog(),
        fn (array $query) => str_contains(strtolower($query['query']), 'count(*)'),
    ));
}

/** This library's own composer.json, decoded. */
function libraryComposer(): array
{
    return json_decode(file_get_contents(libraryPath('composer.json')), true);
}

/**
 * The version of this library's code as deployed — which is the code version of
 * every fixture registered under LIBRARY_PACKAGE.
 */
function libraryVersion(): string
{
    return libraryComposer()['version'];
}

/** Every PHP file under `src/`, concatenated, for the not-a-panel-plugin guards. */
function librarySource(): string
{
    return implode('', array_map(file_get_contents(...), librarySourceFiles()));
}

/**
 * Every PHP file under `src/`, keyed by its path relative to `src/`, for a
 * guard that cares which file something appears in.
 *
 * @return array<string, string>
 */
function librarySourceFiles(): array
{
    $files = [];
    $root = libraryPath('src');

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[ltrim(str_replace($root, '', $file->getPathname()), '/')] = $file->getPathname();
        }
    }

    ksort($files);

    return $files;
}

/** A path inside the library root, whatever directory a test file lives in. */
function libraryPath(string $path = ''): string
{
    return rtrim(dirname(__DIR__).'/'.$path, '/');
}

/**
 * What the panel's topbar render hook draws, on the test app's panel. The
 * notice has no seam of its own — this is the hook a real panel calls, asked
 * the same way.
 */
function renderTopbar(): string
{
    Filament::setCurrentPanel('admin');

    return (string) FilamentView::renderHook(PanelsRenderHook::TOPBAR_END);
}

/** Put an installer-shaped plugin on the test app's panel, under the id the installer uses. */
function installerPluginPresent(): void
{
    Filament::getPanel('admin')->plugin(new InstallerLikePlugin);
}
