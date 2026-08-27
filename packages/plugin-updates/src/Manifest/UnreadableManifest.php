<?php

namespace QcenticEdge\PluginUpdates\Manifest;

use RuntimeException;

/**
 * A manifest the library cannot read as a list of releases.
 *
 * Failing loudly is the point. A manifest that quietly read as empty would
 * report a package several releases behind as owing nothing, which is exactly
 * the silent-stale-database failure this design exists to prevent. Each of the
 * first-party packages carries a test that its own manifest parses, so a typo
 * surfaces in the developer's test run rather than in an operator's panel.
 */
final class UnreadableManifest extends RuntimeException
{
    public static function missing(string $path): self
    {
        return new self("No update manifest at [{$path}]. A package declares one with "
            .'UpdatablePackage::make(...)->manifest(__DIR__."/../database/updates.php").');
    }

    public static function notReleases(string $path): self
    {
        return new self("The update manifest at [{$path}] must return an array of releases, "
            ."keyed by version: ['0.4.0' => ['seed' => false]].");
    }

    public static function notFlags(string $path, string $version): self
    {
        return new self("The release [{$version}] in the update manifest at [{$path}] must be "
            ."an array of flags, such as ['seed' => true].");
    }
}
