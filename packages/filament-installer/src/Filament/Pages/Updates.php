<?php

namespace QcenticEdge\FilamentInstaller\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use QcenticEdge\PluginUpdates\Access\Operator;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use Throwable;
use UnitEnum;

/**
 * Database updates, from the panel — one row per plugin, the way WordPress
 * lists them.
 *
 * These apps are stateless edge containers with no shell and no persistent
 * disk, so a plugin upgrade that ships a migration has nowhere to run it. This
 * page is that missing step. Each plugin declares itself to
 * `qcentic-edge/plugin-updates`, and the operator sees what its database is at,
 * what its code is at, what the pending work will touch and how many rows are
 * in it — then updates one plugin without touching the others.
 *
 * It is a renderer and nothing else. Every question it asks goes to
 * `PluginUpdates::report()`; it reads no registry, no version ledger and no
 * migrator, and it reimplements no part of what the report answers. The
 * installer used to scan every migration path in the application from here,
 * which could only say that *something* was pending — and made every plugin
 * depend on this package to finish its own upgrade. That is gone.
 */
class Updates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Updates';

    protected static ?string $title = 'Updates';

    protected static ?string $slug = 'updates';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 95;

    protected string $view = 'installer::filament.pages.updates';

    /**
     * Every registered package and what it owes, read fresh on each render
     * rather than held on the component.
     *
     * Two reasons, and both matter. A run returns nothing on purpose — what a
     * package owes afterwards is read back through the report — so the list
     * after a click has to be a new read, not a cached one nudged by hand. And
     * a `PackageStatus` counts rows lazily behind a closure, which is not
     * something Livewire could carry between requests anyway.
     *
     * @return array<string, PackageStatus>
     */
    public function statuses(): array
    {
        return PluginUpdates::report()->all();
    }

    public static function canAccess(): bool
    {
        return Operator::present();
    }

    /**
     * Badge the sidebar with the number of plugins needing an update.
     *
     * Packages, not migration files: what an operator acts on is a plugin, and
     * a count of files could not be reconciled with a list of plugins. Note
     * this counts what `owesWork()` says, never a version gap — a plugin whose
     * database has simply never recorded a version is not behind on anything.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = count(PluginUpdates::report()->owing());

        return $count > 0 ? (string) $count : null;
    }

    /**
     * A colour rather than a second reading of the report. The panel only shows
     * this where `getNavigationBadge()` returned a count, and building the whole
     * report twice on every page of the panel to re-answer a question the badge
     * has already answered is a sweep of every declared table of every package
     * for a value nothing will use.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * One action, reused per row through its arguments, so the page does not
     * grow a method per registered package.
     *
     * Rendered only where the report says a run would go ahead. A package that
     * owes work the library would refuse to run carries `unrunnableReason()`
     * instead of a button, because a click there would only ever produce the
     * refusal.
     */
    public function updateAction(): Action
    {
        return Action::make('update')
            ->label('Update')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Update '.$this->titleOf($arguments['package']))
            ->modalDescription('Migrations run against the live database. Take a backup first if the release notes '
                .'call for one, and make sure nobody else is updating this plugin at the same time — nothing here '
                .'stops two operators running it at once.')
            ->modalSubmitActionLabel('Update')
            ->action(function (array $arguments): void {
                abort_unless(Operator::present(), 403);

                $package = $arguments['package'];
                $title = $this->titleOf($package);

                try {
                    PluginUpdates::run($package);
                } catch (Throwable $failure) {
                    Notification::make()
                        ->danger()
                        ->title($title.' was not updated')
                        ->body($failure->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title($title.' is up to date')
                    ->send();
            });
    }

    /** The name the operator sees, read back through the report like everything else. */
    private function titleOf(string $package): string
    {
        return PluginUpdates::report()->status($package)?->title ?? $package;
    }
}
