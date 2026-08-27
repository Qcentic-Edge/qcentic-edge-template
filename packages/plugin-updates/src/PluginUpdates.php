<?php

namespace QcenticEdge\PluginUpdates;

use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Report\UpdateReport;

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
}
