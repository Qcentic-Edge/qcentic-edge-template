<?php

use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\IncompleteDeclaration;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Tests\Fixtures\FixtureSeeder;

it('declares everything a package owns in one call', function () {
    registerFixturePackage();

    $package = PluginUpdates::package('qcentic-edge/fixture-plugin');

    expect($package)->not->toBeNull()
        ->and($package->name())->toBe('qcentic-edge/fixture-plugin')
        ->and($package->displayTitle())->toBe('Fixture Plugin')
        ->and($package->manifestPath())->toBe(fixturePackagePath('updates.php'))
        ->and($package->migrationPath())->toBe(fixturePackagePath('migrations'))
        ->and($package->seederClass())->toBe(FixtureSeeder::class)
        ->and($package->tableNames())->toBe(['fixture_widgets']);
});

it('leaves the optional declarations absent when a package makes none', function () {
    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-tableless')
            ->title('Fixture Tableless')
            ->manifest(fixturePackagePath('updates.php')),
    );

    $package = PluginUpdates::package('qcentic-edge/fixture-tableless');

    expect($package->migrationPath())->toBeNull()
        ->and($package->seederClass())->toBeNull()
        ->and($package->tableNames())->toBe([]);
});

it('enumerates every registered package', function () {
    registerFixturePackage('qcentic-edge/fixture-one');
    registerFixturePackage('qcentic-edge/fixture-two');

    expect(array_keys(PluginUpdates::packages()))
        ->toBe(['qcentic-edge/fixture-one', 'qcentic-edge/fixture-two']);
});

it('is empty until a package registers', function () {
    expect(PluginUpdates::packages())->toBe([]);

    registerFixturePackage();

    expect(PluginUpdates::packages())->toHaveCount(1);
});

it('does not duplicate a package that registers twice', function () {
    registerFixturePackage();
    registerFixturePackage();

    expect(PluginUpdates::packages())->toHaveCount(1);
});

it('keeps the latest declaration when a package registers twice', function () {
    registerFixturePackage();

    PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-plugin')
            ->title('Fixture Plugin, renamed')
            ->manifest(fixturePackagePath('updates.php')),
    );

    expect(PluginUpdates::package('qcentic-edge/fixture-plugin')->displayTitle())
        ->toBe('Fixture Plugin, renamed');
});

it('reports an unregistered package as absent', function () {
    expect(PluginUpdates::package('qcentic-edge/never-registered'))->toBeNull();
});

it('refuses a package that declares no manifest', function () {
    expect(fn () => PluginUpdates::register(
        UpdatablePackage::make('qcentic-edge/fixture-manifestless')->title('Fixture Manifestless'),
    ))->toThrow(IncompleteDeclaration::class, 'qcentic-edge/fixture-manifestless');
});

it('leaves a package that declares no manifest out of the registry', function () {
    try {
        PluginUpdates::register(UpdatablePackage::make('qcentic-edge/fixture-manifestless'));
    } catch (IncompleteDeclaration) {
        //
    }

    expect(PluginUpdates::package('qcentic-edge/fixture-manifestless'))->toBeNull();
});

it('names the missing declaration when a manifest path is asked for anyway', function () {
    expect(fn () => UpdatablePackage::make('qcentic-edge/fixture-manifestless')->manifestPath())
        ->toThrow(IncompleteDeclaration::class, 'manifest');
});
