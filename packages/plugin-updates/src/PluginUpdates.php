<?php

namespace QcenticEdge\PluginUpdates;

use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Report\UpdateReport;
use QcenticEdge\PluginUpdates\Runner\UnrunnablePackage;
use QcenticEdge\PluginUpdates\Runner\UpdateRunner;

/**
 * The entry point a package calls from its own service provider:
 *
 *     PluginUpdates::register(
 *         UpdatablePackage::make('qcentic-edge/filament-seo')
 *             ->title('SEO')
 *             ->manifest(__DIR__.'/../database/updates.php'),
 *     );
 *
 * Packages depend on this library and never on each other, and never on the
 * installer — that dependency arrow is the point of the whole arrangement.
 */
final class PluginUpdates
{
    public static function registry(): PackageRegistry
    {
        return app(PackageRegistry::class);
    }

    public static function register(UpdatablePackage ...$packages): PackageRegistry
    {
        return self::registry()->add(...$packages);
    }

    /** @return array<string, UpdatablePackage> */
    public static function packages(): array
    {
        return self::registry()->all();
    }

    public static function package(string $name): ?UpdatablePackage
    {
        return self::registry()->get($name);
    }

    public static function ledger(): VersionLedger
    {
        return app(VersionLedger::class);
    }

    /**
     * What every registered package owes, right now. This is the only way to
     * read update state — the installer's page, the topbar notice and the run
     * action are all views of it, and nothing else queries it directly.
     */
    public static function report(): UpdateReport
    {
        return app(UpdateReport::class);
    }

    /**
     * Catch one package's database up to its code: the unapplied migrations in
     * its own path, then its seeder if any pending release owes one, then the
     * stored version advanced to the code version. One package at a time, and
     * the same single call whether the database is one release behind or has
     * the package's whole history unapplied.
     *
     * Only ever from an explicit action — an operator's click. Nothing calls
     * this on boot; several replicas starting the same schema change on deploy
     * is the failure that avoids.
     *
     * Returns nothing on purpose. What the package owes afterwards is read back
     * through `report()`, like every other question about update state, so that
     * a caller never holds a private answer alongside the one everything else
     * reads.
     *
     * @throws UnrunnablePackage before anything has been touched, when the
     *                           package is not registered or declared something
     *                           the library will not guess at.
     */
    public static function run(string $package): void
    {
        app(UpdateRunner::class)->run($package);
    }
}
