<?php

namespace App\Policies;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, Media $media): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['user', 'super_admin']);
    }

    public function update(User $user, Media $media): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function updateAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    private function owns(User $user, Media $media): bool
    {
        if ($media->model_type === $user->getMorphClass()
            && (int) $media->model_id === (int) $user->id) {
            return true;
        }

        $ownerId = data_get($media, 'user_id') ?? data_get($media, 'owner_id');

        return $ownerId !== null && (int) $ownerId === (int) $user->id;
    }
}
