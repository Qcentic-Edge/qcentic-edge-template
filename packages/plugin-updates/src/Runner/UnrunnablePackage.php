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
 * Each is a mis-declaration by the package, not a fault of the operator's, so
 * the message names the package and says what to declare.
 */
final class UnrunnablePackage extends RuntimeException
{
    public static function notRegistered(string $package): self
    {
        return new self("No package [{$package}] has registered for updates, so there is nothing to run. "
            .'A package declares itself with PluginUpdates::register(UpdatablePackage::make(...)) '
            .'from its own service provider.');
    }

    public static function withUnreadableDeclaration(string $package, string $problem): self
    {
        return new self("The package [{$package}] cannot be updated, because its own declaration cannot "
            ."be read and so whether it owes a seed is unknown: {$problem} Running it anyway would be "
            .'running blind.');
    }

    public static function withoutCodeVersion(string $package): self
    {
        return new self("The package [{$package}] cannot be updated, because Composer does not know what "
            .'version of its code is deployed and so there is no version to advance its database to. '
            .'Check that it is installed under the same name it registered.');
    }

    /** @param  list<string>  $versions  the pending releases that asked for a seed */
    public static function withoutSeeder(string $package, array $versions): self
    {
        return new self("The package [{$package}] cannot be updated, because the release(s) ["
            .implode(', ', $versions).'] ask for a seed and it declared no seeder. Declare one with '
            .'UpdatablePackage::make(...)->seeder(YourSeeder::class), or set seed => false for those '
            .'releases. Skipping the seed quietly would lose the data those releases meant to add.');
    }
}
