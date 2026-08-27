<?php

namespace QcenticEdge\PluginUpdates\Runner;

use RuntimeException;

/**
 * A package the library will not update, refused before it has touched
 * anything — and the place the refusal is worded.
 *
 * Every refusal here is the same principle the rest of the design turns on:
 * the library refuses to guess. Running with a fact missing would either invent
 * a version an operator's database is then wrongly recorded at, or silently
 * skip work a release declared — both of which report a stale database as
 * healthy, which is the one failure this whole arrangement exists to prevent.
 *
 * The sentences live here rather than in the report, because that is where this
 * package puts operator-facing prose: `IncompleteDeclaration`,
 * `UnreadableManifest` and `UnreachableRegistry` all say what went wrong and
 * what to declare instead, and a data object that returned remediation
 * sentences would be the odd one out.
 *
 * What must not change is that there is exactly one such sentence per refusal.
 * A renderer has to know a run would be refused *before* it draws a button, so
 * `PackageStatus::refusal()` builds the very exception a run would throw and
 * hands it back unthrown; the operator is shown its message and the runner
 * throws the object. Having the exception word the reason a second time, in its
 * own words, is how the two drift apart and an operator is shown one thing and
 * told another.
 */
final class UnrunnablePackage extends RuntimeException
{
    public static function notRegistered(string $package): self
    {
        return new self("No package [{$package}] has registered for updates, so there is nothing to run. "
            .'A package declares itself with PluginUpdates::register(UpdatablePackage::make(...)) '
            .'from its own service provider.');
    }

    /** @param  string  $problem  from `UnreadableManifest`, naming the file and what is wrong with it */
    public static function manifestUnreadable(string $package, string $problem): self
    {
        return self::because($package, 'Its own release manifest cannot be read, so whether it owes a seed is unknown and '
            ."running it would be running blind: {$problem}");
    }

    public static function codeVersionUnknown(string $package): self
    {
        return self::because($package, 'Composer does not know what version of its code is deployed, so there is no version '
            .'to advance its database to. Check that it is installed under the name it registered.');
    }

    /** @param  list<string>  $seedingVersions  the pending releases that asked for a seed */
    public static function seederUndeclared(string $package, array $seedingVersions): self
    {
        return self::because($package, 'The release(s) ['.implode(', ', $seedingVersions).'] ask for a seed and it '
            .'declared no seeder. Declare one with UpdatablePackage::make(...)->seeder(YourSeeder::class), '
            .'or set seed => false for those releases. Skipping the seed quietly would lose the data '
            .'those releases meant to add.');
    }

    /**
     * The shared opening every refusal carries, so each factory above writes
     * only the half that differs. Private: a refusal is one of the cases above
     * and never an arbitrary string handed in from outside.
     */
    private static function because(string $package, string $reason): self
    {
        return new self("The package [{$package}] cannot be updated. {$reason}");
    }
}
