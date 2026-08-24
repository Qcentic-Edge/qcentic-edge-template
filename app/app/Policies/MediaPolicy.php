<?php

namespace App\Policies;

use App\Models\User;

class MediaPolicy
{
    /**
     * Spatie Media Library is not installed yet. Own vs any checks stay
     * empty-safe: missing records or owner keys never throw.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, ?object $media = null): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['user', 'super_admin']);
    }

    public function update(User $user, ?object $media = null): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function updateAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, ?object $media = null): bool
    {
        return $user->hasRole('super_admin') || $this->owns($user, $media);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    private function owns(User $user, ?object $media): bool
    {
        if ($media === null) {
            return false;
        }

        $ownerId = data_get($media, 'user_id')
            ?? data_get($media, 'model_id')
            ?? data_get($media, 'owner_id');

        return $ownerId !== null && (int) $ownerId === (int) $user->id;
    }
}
