<?php

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;
use QcenticEdge\PluginUpdates\Tests\Fixtures\InstallerLikePlugin;
use QcenticEdge\PluginUpdates\Tests\Fixtures\RunPackageSeeder;
use QcenticEdge\PluginUpdates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

// Host/ is bound nowhere here on purpose: each file in it boots a different
// shape of host — one with no Livewire to render the notice with, one where the
// library's own provider never registered — so each names its own base class at
// the top of the file rather than sharing a directory's.

const HISTORY_PACKAGE = 'qcentic-edge/history-plugin';
const INSTALLED_PACKAGE = 'qcentic-edge/plugin-updates';
const OUT_OF_ORDER_PACKAGE = 'qcentic-edge/out-of-order-plugin';
const BROKEN_PACKAGE = 'qcentic-edge/broken-plugin';

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
function registerHistoryPackage(string $name = HISTORY_PACKAGE): UpdatablePackage
{
    $package = UpdatablePackage::make($name)
        ->title('History Plugin')
        ->manifest(historyPackagePath('updates.php'))
        ->migrations(historyPackagePath('migrations'))
        ->seeder(FixtureSeeder::class)
        ->tables(['history_widgets', 'history_notes', 'history_tags']);

    PluginUpdates::register($package);

    return $package;
}

/**
 * A package that really is installed under a Composer name, so a test can put
 * the stored version, the code version and the newest release all at the same
 * point. The library itself is the only package guaranteed to be installed
 * while its own suite runs, so it stands in for one.
 */
function registerInstalledPackage(?string $manifest = null): UpdatablePackage
{
    $package = UpdatablePackage::make(INSTALLED_PACKAGE)
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
 * Which migration files each release of the history fixture shipped.
 *
 * This map lives in the test suite and must never grow anywhere in `src/`.
 * The library reads schema state from the migrator's own per-file ledger
 * precisely so that it never needs to know which release a file belongs to;
 * a copy of that fact inside the library would drift the same way the
 * rejected manifest schema flag would have.
 *
 * @return array<string, list<string>>
 */
function historyReleases(): array
{
    return [
        '0.1.0' => ['2026_01_01_000000_create_history_widgets_table'],
        '0.2.0' => ['2026_02_01_000000_create_history_notes_table'],
        '0.3.0' => [],
        '0.4.0' => [],
        '0.5.0' => [
            '2026_05_01_000000_add_colour_to_history_widgets_table',
            '2026_05_01_000001_create_history_tags_table',
        ],
    ];
}

/**
 * Run every history migration that shipped at or before this release, and
 * record it in Laravel's own ledger — which is what an operator's database
 * looks like when their site last deployed at that release.
 */
function applyHistoryThrough(string $release): void
{
    foreach (historyReleases() as $version => $migrations) {
        if (version_compare($version, $release, '>')) {
            continue;
        }

        foreach ($migrations as $migration) {
            applyFixtureMigration(historyPackagePath('migrations/'.$migration.'.php'));
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
 * Put the database where a site that last deployed at this release would be:
 * its migrations applied and its stored version recorded.
 */
function placeHistoryAt(string $release, string $name = HISTORY_PACKAGE): void
{
    applyHistoryThrough($release);

    PluginUpdates::ledger()->record($name, $release);
}

/** What the report says the history fixture owes. */
function historyStatus(string $name = HISTORY_PACKAGE): PackageStatus
{
    return PluginUpdates::report()->status($name)
        ?? throw new RuntimeException("No package [{$name}] is registered; a test that asks "
            .'what the history fixture owes has to call registerHistoryPackage() first.');
}

function runPackagePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/RunPackage/'.$path, '/');
}

/**
 * The fixture the run tests drive: four releases, four migration files, a
 * seeder, and a third migration a test can make fail.
 *
 * Registered under the library's own Composer name, because a run refuses a
 * package whose deployed version Composer does not know and the library is the
 * only package guaranteed to be installed while its own suite runs. Its
 * releases all sit below that version, and none of them equals it, so a test
 * can tell "advanced to the code version" apart from "advanced to the newest
 * pending release".
 *
 * Pass `withMigrations: false` for a package that owes only a seed, and
 * `withSeeder: false` for one that declares none.
 */
function registerRunPackage(bool $withMigrations = true, bool $withSeeder = true): UpdatablePackage
{
    $package = UpdatablePackage::make(INSTALLED_PACKAGE)
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
 * Which migration file each release of the run fixture shipped. Like
 * `historyReleases()`, this map lives in the test suite and must never grow
 * anywhere in `src/`.
 *
 * @return array<string, list<string>>
 */
function runReleases(): array
{
    return [
        '0.0.1' => ['2026_01_01_000000_create_run_widgets_table'],
        '0.0.2' => ['2026_02_01_000000_create_run_notes_table'],
        '0.0.3' => ['2026_03_01_000000_create_run_tags_table'],
        '0.0.4' => ['2026_04_01_000000_add_colour_to_run_widgets_table'],
    ];
}

/** Run every run-fixture migration that shipped at or before this release. */
function applyRunThrough(string $release): void
{
    foreach (runReleases() as $version => $migrations) {
        if (version_compare($version, $release, '>')) {
            continue;
        }

        foreach ($migrations as $migration) {
            applyFixtureMigration(runPackagePath('migrations/'.$migration.'.php'));
        }
    }
}

/** Put the database where a site that last deployed the run fixture at this release would be. */
function placeRunAt(string $release): void
{
    applyRunThrough($release);

    PluginUpdates::ledger()->record(INSTALLED_PACKAGE, $release);
}

/** What the report says the run fixture owes — read back the same way anything else reads it. */
function runStatus(): PackageStatus
{
    return PluginUpdates::report()->status(INSTALLED_PACKAGE)
        ?? throw new RuntimeException('No run fixture is registered; call registerRunPackage() first.');
}

/**
 * The version of the run fixture's code as deployed. It is the library's own,
 * because the run fixture registers under the library's Composer name.
 */
function runCodeVersion(): string
{
    return libraryComposer()['version'];
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

/** Every PHP file under `src/`, concatenated, for the not-a-panel-plugin guards. */
function librarySource(): string
{
    $source = '';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(libraryPath('src'))) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $source .= file_get_contents($file->getPathname());
        }
    }

    return $source;
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
