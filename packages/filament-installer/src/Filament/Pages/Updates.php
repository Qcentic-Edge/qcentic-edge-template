<?php

namespace QcenticEdge\FilamentInstaller\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use QcenticEdge\FilamentInstaller\Support\InstallerState;
use Throwable;
use UnitEnum;

/**
 * Database updates, from the panel.
 *
 * The installer runs migrations once at first boot and then locks itself. When
 * a plugin upgrade later ships a migration there is nowhere to run it: these
 * apps are stateless edge containers with no shell and no persistent disk.
 * This page is that missing step — the panel equivalent of WordPress asking to
 * update its database after a plugin upgrade.
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

    /** @var list<string> */
    public array $pending = [];

    public ?string $output = null;

    public function mount(): void
    {
        $this->pending = InstallerState::pendingMigrations();
    }

    public static function canAccess(): bool
    {
        return static::isOperator(auth()->user());
    }

    /**
     * Badge the sidebar when something is waiting, the way an update count
     * works everywhere else.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = count(InstallerState::pendingMigrations());

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return count(InstallerState::pendingMigrations()) > 0 ? 'warning' : null;
    }

    public function runAction(): Action
    {
        return Action::make('run')
            ->label('Run updates')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Run pending database updates')
            ->modalDescription('Migrations run against the live database. Take a backup first if the plugin release notes call for one.')
            ->modalSubmitActionLabel('Run updates')
            ->disabled(fn (): bool => $this->pending === [])
            ->action(function (): void {
                if (! static::isOperator(auth()->user())) {
                    abort(403);
                }

                try {
                    $this->output = trim(InstallerState::migrate());
                } catch (Throwable $e) {
                    $this->output = $e->getMessage();

                    Notification::make()
                        ->danger()
                        ->title('Update failed')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                $before = count($this->pending);
                $this->pending = InstallerState::pendingMigrations();

                Notification::make()
                    ->success()
                    ->title('Database updated')
                    ->body($before.' '.str('migration')->plural($before).' ran.')
                    ->send();
            });
    }

    /**
     * Only a super admin runs migrations. Falls back to "any authenticated
     * panel user" on an app with no role package, which is the same posture
     * the installer itself takes.
     */
    protected static function isOperator(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole('super_admin');
        }

        return true;
    }
}
