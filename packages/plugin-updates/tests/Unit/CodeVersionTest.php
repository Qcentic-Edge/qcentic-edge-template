<?php

use Composer\InstalledVersions;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Support\CodeVersion;

it('resolves the deployed version through Composer', function () {
    expect(CodeVersion::for('illuminate/support'))
        ->toBe(InstalledVersions::getPrettyVersion('illuminate/support'));
});

it('resolves the version a package declares in its own composer.json', function () {
    // This library is consumed as a path repository everywhere it is used, and
    // a path package's version is the one its composer.json declares. Reading
    // this library's own version proves that route resolves.
    expect(CodeVersion::for('qcentic-edge/plugin-updates'))
        ->toBe(libraryComposer()['version']);
});

it('reports a package that is not installed as absent', function () {
    expect(CodeVersion::for('qcentic-edge/not-installed-anywhere'))->toBeNull();
});

it('reads a registered package its own code version', function () {
    $package = UpdatablePackage::make('qcentic-edge/plugin-updates')
        ->title('Plugin Updates')
        ->manifest(fixturePackagePath('updates.php'));

    expect($package->codeVersion())->toBe(CodeVersion::for('qcentic-edge/plugin-updates'));
});
