<?php

namespace QcenticEdge\PluginUpdates;

use Illuminate\Support\ServiceProvider;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;

/**
 * Deliberately not a Filament plugin: no panel plugin object, no page, no
 * resource, no navigation item. The library binds the registry that packages
 * declare themselves to and the ledger that records where each package's
 * database has got to, and nothing else.
 *
 * A plain Laravel ServiceProvider rather than Spatie's PackageServiceProvider,
 * which the plugins in this workstation use. That base class earns its keep
 * when a package publishes config, views, translations or migrations; this one
 * publishes none of those and never will, so extending it would add a
 * dependency to configure nothing. Considered and settled — please leave it.
 */
class PluginUpdatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PackageRegistry::class);
        $this->app->singleton(VersionLedger::class);
    }
}
