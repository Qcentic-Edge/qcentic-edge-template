<?php

namespace QcenticEdge\FilamentMediaDrive\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaDriveCatalog
{
    public const DISK = 's3';

    /**
     * @return Collection<int, Media>
     */
    public static function visibleTo(?Authenticatable $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        return Media::query()
            ->where('disk', self::DISK)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Media $media): bool => Gate::forUser($user)->allows('view', $media))
            ->values();
    }

    public static function canAttach(?Authenticatable $user, Media $media): bool
    {
        if ($user === null) {
            return false;
        }

        if ($media->disk !== self::DISK) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $media);
    }
}
