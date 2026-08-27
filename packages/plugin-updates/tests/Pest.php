<?php

use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;
use QcenticEdge\PluginUpdates\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

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
