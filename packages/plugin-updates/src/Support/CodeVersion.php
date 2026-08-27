<?php

namespace QcenticEdge\PluginUpdates\Support;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * The version of a package's code as deployed, read from Composer's own
 * runtime data. This resolves path repositories correctly — a path package
 * reports the version declared in its composer.json — which matters because
 * every first-party package is consumed as a path repository during
 * development and as a VCS package in production.
 */
final class CodeVersion
{
    /** Null when the package is not installed, or is installed without a version. */
    public static function for(string $package): ?string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return null;
        }

        try {
            return InstalledVersions::getPrettyVersion($package);
        } catch (OutOfBoundsException) {
            return null;
        }
    }
}
