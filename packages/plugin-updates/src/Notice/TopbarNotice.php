<?php

namespace QcenticEdge\PluginUpdates\Notice;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use QcenticEdge\PluginUpdates\Access\Operator;
use QcenticEdge\PluginUpdates\PluginUpdates;

/**
 * The library's own way of telling an operator that a package's database is
 * behind, on a site that has no installer plugin to tell them.
 *
 * This is the second of the two renderers and the reason a package is usable on
 * its own: story 9, an operator with no installer wants each package to say so
 * itself rather than run a stale schema silently. It hangs off the panel's
 * topbar render hook, so it is visible from every page of the panel rather than
 * from one screen an operator has to go and find (story 10) — and it says
 * nothing at all when nothing is owed (story 13).
 */
final class TopbarNotice
{
    /**
     * The panel plugin id `filament-installer` registers itself under.
     *
     * Presence is detected by asking the panel whether a plugin with this id is
     * registered on it — a string, resolved at runtime, against Filament's own
     * API. Nothing here imports an installer class, and the library must never
     * require `qcentic-edge/filament-installer`: the dependency arrow points
     * from packages to this library and never to the installer, and inverting
     * it is the entire point of this effort.
     *
     * Asking the panel rather than asking Composer is also the more accurate
     * question. The installer's install flow is plain routes, so a site can
     * have the package installed without registering its panel plugin — and
     * then there is no Updates page, and this notice is the only surface there
     * is.
     */
    public const INSTALLER_PLUGIN_ID = 'installer';

    public static function register(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            static fn (): string|View => self::render(),
        );
    }

    /**
     * Nothing but a decision about whether to render and a view that renders.
     * What is owed, and whether it can be run, are read from the report and
     * reimplemented nowhere.
     */
    public static function render(): string|View
    {
        if (self::suppressed() || ! Operator::signedIn()) {
            return '';
        }

        if (! PluginUpdates::report()->anythingOwed()) {
            return '';
        }

        return view('plugin-updates::topbar');
    }

    /**
     * With the installer present its Updates page is the one place update state
     * is shown, so the notice steps aside and the panel keeps exactly one
     * updates surface.
     */
    public static function suppressed(): bool
    {
        return Filament::getCurrentPanel()?->hasPlugin(self::INSTALLER_PLUGIN_ID) ?? false;
    }
}
