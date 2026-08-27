<?php

namespace QcenticEdge\PluginUpdates;

use Illuminate\Support\ServiceProvider;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Report\UpdateReport;

/**
 * Deliberately not a Filament plugin: no panel plugin object, no page, no
 * resource, no navigation item. The library binds the registry that packages
 * declare themselves to, the ledger that records where each package's database
 * has got to, and the report that reads the two of them, and nothing else.
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

        // Bound, deliberately not a singleton. The report is a read of state
        // that moves underneath it — the migration ledger, the version ledger
        // and the row counts all change while the panel is open — so a run that
        // finishes and then asks again must get the new answer, not the one it
        // was holding. Every resolve builds a fresh report over the singleton
        // registry and ledger.
        $this->app->bind(UpdateReport::class);
    }
}
