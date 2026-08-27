<?php

namespace QcenticEdge\PluginUpdates;

use Livewire\Livewire;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Notice\TopbarNotice;
use QcenticEdge\PluginUpdates\Notice\UpdatesNotice;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Report\UpdateReport;
use QcenticEdge\PluginUpdates\Runner\UpdateRunner;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Still deliberately not a Filament plugin: no panel plugin object, no page, no
 * resource, no navigation item. It does depend on Filament, because it renders
 * a notice into the panel's topbar and needs Filament's render hooks to do it —
 * but depending on Filament is not what makes a package a panel plugin here.
 * What does is the `filament-` prefix, a page and a direct install; this library
 * has none of the three and arrives transitively as a dependency of the
 * packages that use it.
 *
 * Spatie's `PackageServiceProvider`, like every plugin in this workstation and
 * as AGENTS.md asks. The library ships one publishable thing — the two Blade
 * views the topbar notice renders — and `->hasViews()` is precisely what that
 * base class is for; a hand-rolled `loadViewsFrom()` beside a rationale for not
 * extending it was a rationale that had outlived its own facts. It still ships
 * no config, no translations and no migrations of its own, and the version
 * ledger's table is ensured in code rather than by a migration file on purpose
 * — see `Ledger\VersionLedger`.
 */
class PluginUpdatesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('plugin-updates')
            ->hasViews('plugin-updates');
    }

    public function packageRegistered(): void
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
    public function packageBooted(): void
    {
        // After every provider has booted, because Laravel's discovery order
        // does not promise that Livewire and Filament have registered theirs by
        // the time this one boots — and in a package's own test app they may
        // never register at all.
        $this->app->booted(function (): void {
            if (! $this->hostRendersNotice()) {
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
     * topbar, so it needs both halves and the guard asks for both. Livewire
     * alone was not enough: a host with Livewire and no panel still reached
     * `FilamentView` and `PanelsRenderHook`, and hung a topbar hook on a panel
     * registry that was never going to be asked for it.
     *
     * Both questions are asked of the container, and that is the whole trick.
     * Neither `livewire` nor `filament` is a class name — they are string
     * bindings that only Livewire's and Filament's own service providers make —
     * so a host that has the package installed but never registered its
     * provider answers no here, which is the answer that matters. Two hosts
     * look like that. One has never installed the package at all, and touching
     * the facade there is a fatal "class not found". The other has it installed
     * and unregistered: the facade's accessor is a concrete class, so it
     * auto-wires happily and then dies reaching for a binding only that
     * provider makes. Please do not "simplify" either conjunct to
     * `class_exists()`: that is true in the second host, and the second host is
     * the one that crashes.
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
    private function hostRendersNotice(): bool
    {
        return $this->app->bound('livewire') && $this->app->bound('filament');
    }
}
