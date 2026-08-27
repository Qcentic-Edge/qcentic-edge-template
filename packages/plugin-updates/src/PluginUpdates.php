<?php

namespace QcenticEdge\PluginUpdates;

use QcenticEdge\PluginUpdates\Registry\IncompleteDeclaration;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UnreachableRegistry;
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
    /**
     * The one shared registry, and never a stand-in for it.
     *
     * The library's own provider binds it as a singleton. Without that binding
     * the container would still hand back a `PackageRegistry` — it is a
     * concrete class with nothing to inject — and that registry would be a
     * throwaway: a package would declare itself into an object discarded at the
     * end of the call, and every package would then report its database as up
     * to date. So the binding is checked rather than assumed, and its absence
     * is an exception rather than an empty list.
     *
     * @throws UnreachableRegistry when the library's own service provider is
     *                             not registered, so there is no shared
     *                             registry to reach.
     */
    public static function registry(): PackageRegistry
    {
        if (! app()->bound(PackageRegistry::class)) {
            throw UnreachableRegistry::providerNotRegistered(PluginUpdatesServiceProvider::class);
        }

        return app(PackageRegistry::class);
    }

    /**
     * @throws UnreachableRegistry when the library's own provider is missing,
     *                             so the declaration would go nowhere.
     * @throws IncompleteDeclaration when the package declares no manifest.
     */
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

    /**
     * What every registered package owes, right now. This is the only way to
     * read update state — the installer's page, the topbar notice and the run
     * action are all views of it, and nothing else queries it directly.
     *
     * There is deliberately no way to reach the version ledger from here.
     * Reading it is `report()`, and the only thing that writes it is `run()`.
     * A public accessor beside those two would be a second view of update
     * state — the exact hole the one-seam rule exists to close — and a writable
     * one would let a caller record a version no run ever earned, which reports
     * a stale database as healthy for ever after.
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
