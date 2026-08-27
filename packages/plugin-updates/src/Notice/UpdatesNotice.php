<?php

namespace QcenticEdge\PluginUpdates\Notice;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use QcenticEdge\PluginUpdates\Access\Operator;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use Throwable;

/**
 * The topbar notice itself: one line per package that owes work, naming the
 * package and carrying the action that brings it up to date.
 *
 * A Livewire component because the action has to run server-side from wherever
 * in the panel the operator happens to be, and the topbar is rendered by
 * whatever page they are on. It is a renderer and nothing else — every question
 * it asks goes to `PluginUpdates::report()`, and it reimplements no part of it.
 *
 * Deliberately not a Filament page: it has no route, no navigation entry and no
 * resource, and the library still registers none of those.
 */
class UpdatesNotice extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * Why the last attempt to read the report failed, or null when it did not.
     *
     * Not a Livewire property: it is re-derived by `owing()` on every render and
     * means nothing between requests.
     */
    protected ?string $reportFailure = null;

    /**
     * The packages with something to say, read fresh on every render — which is
     * what makes the notice disappear of its own accord once the last package
     * has been caught up.
     *
     * Guarded, because this component is rendered into the topbar of every page
     * of the panel. Building the report touches the database before it has said
     * anything, so a database that has gone away throws from here — and an
     * unguarded throw would take the whole page down with it, not just the
     * strip along its top. That answers with an empty list and a reason in
     * `reportFailure()` instead.
     *
     * @return array<string, PackageStatus>
     */
    public function owing(): array
    {
        $this->reportFailure = null;

        if (! Operator::present()) {
            return [];
        }

        try {
            return PluginUpdates::report()->owing();
        } catch (Throwable $failure) {
            $this->reportFailure = $failure->getMessage();

            return [];
        }
    }

    /**
     * Why the last read of the report failed, or null when it did not — which is
     * what tells a panel with nothing owed apart from one that could not be
     * asked.
     *
     * Recorded by `owing()` rather than read on its own, so the notice makes one
     * attempt at the report per render and the reason it shows belongs to that
     * attempt. The view reads the list first for the same reason.
     */
    public function reportFailure(): ?string
    {
        return $this->reportFailure;
    }

    /**
     * One action, reused per package through its arguments, so the notice does
     * not grow a method per registered package.
     *
     * Offered only where the report says a run would go ahead; a package that
     * owes work the library would refuse to run is rendered as needing
     * attention instead, with the reason, and no button at all.
     */
    public function updateAction(): Action
    {
        return Action::make('update')
            ->label('Update')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->size(Size::Small)
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Update '.$this->titleOf($arguments['package']))
            ->modalDescription('Migrations run against the live database. Take a backup first if the release notes call for one.')
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

    public function render(): View
    {
        return view('plugin-updates::livewire.notice');
    }

    /** The name the operator sees, read back through the report like everything else. */
    private function titleOf(string $package): string
    {
        return PluginUpdates::report()->status($package)?->title ?? $package;
    }
}
