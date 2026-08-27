<?php

namespace QcenticEdge\PluginUpdates\Runner;

use RuntimeException;

/**
 * A package the library will not update, refused before it has touched
 * anything.
 *
 * Every refusal here is the same principle the rest of the design turns on:
 * the library refuses to guess. Running with a fact missing would either invent
 * a version an operator's database is then wrongly recorded at, or silently
 * skip work a release declared — both of which report a stale database as
 * healthy, which is the one failure this whole arrangement exists to prevent.
 *
 * The reasons themselves live on `PackageStatus::unrunnableReason()`, not here.
 * A renderer has to know a run would be refused *before* it draws a button, so
 * the reason has to be part of what the report says about a package; having the
 * exception state it a second time in its own words is how the two drift apart
 * and an operator is shown one thing and told another.
 */
final class UnrunnablePackage extends RuntimeException
{
    public static function notRegistered(string $package): self
    {
        return new self("No package [{$package}] has registered for updates, so there is nothing to run. "
            .'A package declares itself with PluginUpdates::register(UpdatablePackage::make(...)) '
            .'from its own service provider.');
    }

    /** @param  string  $reason  from `PackageStatus::unrunnableReason()` — the same words the operator was shown */
    public static function because(string $package, string $reason): self
    {
        return new self("The package [{$package}] cannot be updated. {$reason}");
    }
}
