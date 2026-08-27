<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;
use QcenticEdge\PluginUpdates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

const HISTORY_PACKAGE = 'qcentic-edge/history-plugin';

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


/*
|--------------------------------------------------------------------------
| The history fixture
|--------------------------------------------------------------------------
|
| A package with five releases and four migration files, so a test can place a
| database at any point in that history and ask the report what the site owes.
| The multi-version path is the one exercised least in development and most in
| the field, so it is the one that gets a fixture of its own.
|
*/

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
    return PluginUpdates::report()->status($name);
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
