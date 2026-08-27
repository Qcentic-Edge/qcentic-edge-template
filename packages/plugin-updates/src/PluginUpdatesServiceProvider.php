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
 */
class PluginUpdatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PackageRegistry::class);
        $this->app->singleton(VersionLedger::class);
    }
}
