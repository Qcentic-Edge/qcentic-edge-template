<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;
use QcenticEdge\PluginUpdates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

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
