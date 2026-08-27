<?php

namespace QcenticEdge\PluginUpdates;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Notice\TopbarNotice;
use QcenticEdge\PluginUpdates\Notice\UpdatesNotice;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Report\UpdateReport;
use QcenticEdge\PluginUpdates\Runner\UpdateRunner;

/**
 * Still deliberately not a Filament plugin: no panel plugin object, no page, no
 * resource, no navigation item. It does now depend on Filament, because it
 * renders a notice into the panel's topbar and needs Filament's render hooks to
 * do it — but depending on Filament is not what makes a package a panel plugin
 * here. What does is the `filament-` prefix, a page and a direct install; this
 * library has none of the three and arrives transitively as a dependency of the
 * packages that use it.
 *
 * A plain Laravel ServiceProvider rather than Spatie's PackageServiceProvider,
 * which the plugins in this workstation use. That base class earns its keep
 * when a package publishes config, translations or migrations; this one
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

        // Likewise bound rather than shared: a runner reads the report at the
        // start of every run, and holding one from an earlier request would be
        // reading a database that has since moved.
        $this->app->bind(UpdateRunner::class);
    }

    /**
     * Registering the notice does no work of any kind: the render hook is a
     * closure the panel calls while drawing a page, and nothing here reads the
     * database, the registry or the migration ledger. Nothing runs on boot.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'plugin-updates');

        // After every provider has booted, because Laravel's discovery order
        // does not promise that Livewire and Filament have registered theirs by
        // the time this one boots — and in a package's own test app they may
        // never register at all.
        $this->app->booted(function (): void {
            if (! $this->hostRendersLivewire()) {
                return;
            }

            Livewire::component('plugin-updates.notice', UpdatesNotice::class);

            TopbarNotice::register();
        });
    }

    /**
     * Whether this host can actually draw the notice — and a quiet no when it
     * cannot.
     *
     * The notice is a Livewire component rendered into a Filament panel's
     * topbar, so a host with no Livewire has nothing to render it with. Two
     * hosts look like that. One has never installed Livewire, and touching the
     * facade there is a fatal "class not found". The other has it installed but
     * has not registered its provider: the facade's accessor is a concrete
     * class, so it auto-wires happily and then dies reaching for
     * `livewire.finder`, a binding only that provider makes. Asking the
     * container catches both, because neither host has the binding.
     *
     * Skipping is the whole point. Most packages take this library for its
     * reporting — what a release owes, and a way to run it — and never render
     * anything; their own test application is a bare Laravel app with no panel
     * in it. Registering a notice such an app cannot draw forced every one of
     * them to boot Filament and Livewire, in a particular order, to satisfy a
     * surface they will never show. A package is not made to boot a panel to
     * declare that its database is behind.
     *
     * This is emphatically not the answer to a missing registry. That one is
     * loud — see `Registry\UnreachableRegistry` — because a package that cannot
     * reach the registry reports a stale database as healthy, whereas a package
     * that cannot render a notice still reports correctly to everything that
     * asks. An optional surface is skipped; a lost declaration is refused.
     */
    private function hostRendersLivewire(): bool
    {
        return class_exists(Livewire::class) && $this->app->bound('livewire');
    }
}
