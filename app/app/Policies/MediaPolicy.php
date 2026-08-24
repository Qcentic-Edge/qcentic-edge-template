<?php

namespace App\Policies;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $user->can('ViewAny:Media')
            || $user->can('View:Media');
    }

    public function view(User $user, Media $media): bool
    {
        if ($this->isSuperAdmin($user) || $user->can('ViewAny:Media')) {
            return true;
        }

        return $user->can('View:Media') && $this->owns($user, $media);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('Create:Media');
    }

    public function update(User $user, Media $media): bool
    {
        if ($this->isSuperAdmin($user) || $user->can('UpdateAny:Media')) {
            return true;
        }

        return $user->can('Update:Media') && $this->owns($user, $media);
    }

    public function updateAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('UpdateAny:Media');
    }

    public function delete(User $user, Media $media): bool
    {
        if ($this->isSuperAdmin($user) || $user->can('DeleteAny:Media')) {
            return true;
        }

        return $user->can('Delete:Media') && $this->owns($user, $media);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('DeleteAny:Media');
    }

    private function isSuperAdmin(User $user): bool
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
