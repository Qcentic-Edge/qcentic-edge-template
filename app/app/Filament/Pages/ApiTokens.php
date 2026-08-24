<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ApiTokens extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'API tokens';

    protected static ?string $title = 'API tokens';

    protected static ?string $slug = 'api-tokens';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $plainTextToken = null;

    public static function userMenuAction(): Action
    {
        return Action::make('apiTokens')
            ->label('API tokens')
            ->icon(Heroicon::OutlinedKey)
            ->url(fn (): string => static::getUrl());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('New token')
                    ->description('Copy this token now. It will not be shown again.')
                    ->visible(fn (): bool => filled($this->plainTextToken))
                    ->schema([
                        Text::make(fn (): string => (string) $this->plainTextToken)->copyable(),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label('Create token')
                ->icon(Heroicon::OutlinedKey)
                ->schema([
                    TextInput::make('name')
                        ->label('Token name')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $user = User::query()->find(auth()->id());

                    if ($user === null) {
                        return;
                    }

                    $this->plainTextToken = $user->createToken($data['name'])->accessToken;

                    Notification::make()
                        ->title('Token created')
                        ->body('Copy it now. It will not be shown again.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
