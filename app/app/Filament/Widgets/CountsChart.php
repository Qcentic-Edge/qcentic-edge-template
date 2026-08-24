<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CountsChart extends ChartWidget
{
    protected ?string $heading = 'Users and media';

    protected ?string $maxHeight = '300px';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);

        parent::mount();
    }

    /**
     * @return array{users: int, media: int}
     */
    public static function countsFor(?User $user): array
    {
        return [
            'users' => self::visibleUserCount($user),
            'media' => self::visibleMediaCount($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $actor = auth()->user();
        $counts = self::countsFor($actor instanceof User ? $actor : null);

        return [
            'datasets' => [
                [
                    'label' => 'Count',
                    'data' => [$counts['users'], $counts['media']],
                ],
            ],
            'labels' => ['Users', 'Media'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private static function visibleUserCount(?User $user): int
    {
        if ($user === null || Gate::forUser($user)->denies('viewAny', User::class)) {
            return 0;
        }

        return User::query()->count();
    }

    private static function visibleMediaCount(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        $query = Media::query();

        if ($user->hasRole('super_admin') || $user->can('ViewAny:Media')) {
            return $query->count();
        }

        if (! $user->can('View:Media')) {
            return 0;
        }

        return $query
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->count();
    }
}
