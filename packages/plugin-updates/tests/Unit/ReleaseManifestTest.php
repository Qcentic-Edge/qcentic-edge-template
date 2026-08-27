<?php

use QcenticEdge\PluginUpdates\Manifest\ReleaseManifest;
use QcenticEdge\PluginUpdates\Manifest\UnreadableManifest;

it('reads the releases a package declared', function () {
    expect(ReleaseManifest::read(historyPackagePath('updates.php'))->versions())
        ->toBe(['0.1.0', '0.2.0', '0.3.0', '0.4.0', '0.5.0']);
});

it('sorts releases by version rather than by string', function () {
    // '0.10.0' sorts below '0.9.0' as a string and above it as a version.
    expect(ReleaseManifest::read(outOfOrderPackagePath('updates.php'))->versions())
        ->toBe(['0.1.0', '0.2.0', '0.9.0', '0.10.0']);
});

it('reads a manifest listed out of order correctly', function () {
    $manifest = ReleaseManifest::read(outOfOrderPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.2.0', null))->toBe(['0.9.0', '0.10.0']);
});

it('counts 0.10.0 as above 0.9.0', function () {
    $manifest = ReleaseManifest::read(outOfOrderPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.9.0', null))->toBe(['0.10.0']);
});

it('treats a package with no stored version as being at the oldest release', function () {
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween(null, null))
        ->toBe(['0.1.0', '0.2.0', '0.3.0', '0.4.0', '0.5.0']);
});

it('leaves the stored release itself out of the pending list', function () {
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.5.0', null))->toBe([]);
});

it('reports a stored version between two releases correctly', function () {
    // A version the manifest never listed still orders against the ones it did.
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.3.1', null))->toBe(['0.4.0', '0.5.0']);
});

it('leaves out a release the deployed code has not reached', function () {
    // The upper bound. A release the author declared before bumping the
    // package's composer.json is not work the deployed code can do, so it is
    // not work the site owes.
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.2.0', '0.4.0'))->toBe(['0.3.0', '0.4.0'])
        ->and($manifest->pendingBetween(null, '0.2.0'))->toBe(['0.1.0', '0.2.0']);
});

it('includes the code version itself in the pending list', function () {
    // At or below, not below: the release the code is at is the one the site is
    // catching up to, and dropping it would lose the seed it asked for.
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.2.0', '0.3.0'))->toBe(['0.3.0'])
        ->and($manifest->seedingAmong($manifest->pendingBetween('0.2.0', '0.3.0')))
        ->toBe(['0.3.0']);
});

it('bounds the pending list above by version rather than by string', function () {
    $manifest = ReleaseManifest::read(outOfOrderPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.2.0', '0.9.0'))->toBe(['0.9.0']);
});

it('leaves the pending list unbounded above when the code version is unknown', function () {
    // Composer does not know the package, so there is nothing to filter
    // against — and filtering against nothing would hide every release.
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->pendingBetween('0.3.0', null))->toBe(['0.4.0', '0.5.0']);
});

it('unions the seed flags of the releases it is given', function () {
    $manifest = ReleaseManifest::read(historyPackagePath('updates.php'));

    expect($manifest->seedingAmong(['0.4.0', '0.5.0']))->toBe([])
        ->and($manifest->seedingAmong(['0.3.0', '0.4.0', '0.5.0']))->toBe(['0.3.0']);
});

it('treats a release that declares no seed key as declining one', function () {
    expect(ReleaseManifest::read(fixturePackagePath('updates.php'))->seedingAmong(['0.1.0']))
        ->toBe([]);
});

it('refuses a manifest that is not there', function () {
    expect(fn () => ReleaseManifest::read(historyPackagePath('nowhere.php')))
        ->toThrow(UnreadableManifest::class, 'nowhere.php');
});

it('refuses a manifest that returns something other than releases', function () {
    $path = sys_get_temp_dir().'/plugin-updates-bad-manifest.php';
    file_put_contents($path, '<?php return "not a manifest";');

    expect(fn () => ReleaseManifest::read($path))->toThrow(UnreadableManifest::class);

    unlink($path);
});

it('refuses a release whose entry is not a set of flags', function () {
    $path = sys_get_temp_dir().'/plugin-updates-bad-release.php';
    file_put_contents($path, '<?php return ["0.1.0" => true];');

    expect(fn () => ReleaseManifest::read($path))->toThrow(UnreadableManifest::class, '0.1.0');

    unlink($path);
});
