<?php

namespace Mamenein\FilamentMediaDrive\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Mamenein\FilamentMediaDrive\Forms\Components\MediaPicker;
use Mamenein\FilamentMediaDrive\Support\MediaDriveCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DrivePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Drive';

    protected static ?string $title = 'Drive';

    protected static ?string $slug = 'drive';

    protected static bool $shouldRegisterNavigation = false;

    public string $viewMode = 'grid';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->allows('viewAny', Media::class);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-media-drive::pages.drive-browser'),
                MediaPicker::make('mediaId')
                    ->label(__('filament-media-drive::drive.picker.label')),
            ])
            ->statePath('data');
    }

    public function setLayout(string $layout): void
    {
        $this->viewMode = $layout === 'list' ? 'list' : 'grid';
    }

    /**
     * @return Collection<int, Media>
     */
    public function getItems(): Collection
    {
        return MediaDriveCatalog::visibleTo(auth()->user());
    }

    public function attach(): void
    {
        $this->content->getState();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('grid')
                ->label(__('filament-media-drive::drive.layout.grid'))
                ->icon(Heroicon::OutlinedSquares2x2)
                ->action(fn (): mixed => $this->setLayout('grid')),
            Action::make('list')
                ->label(__('filament-media-drive::drive.layout.list'))
                ->icon(Heroicon::OutlinedBars3)
                ->action(fn (): mixed => $this->setLayout('list')),
            Action::make('attach')
                ->label(__('filament-media-drive::drive.picker.attach'))
                ->action(fn (): mixed => $this->attach()),
        ];
    }
}
