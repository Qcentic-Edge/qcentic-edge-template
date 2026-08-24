<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

if (! function_exists('driveGrantMediaPermissions')) {
    /**
     * @param  list<string>  $permissions
     */
    function driveGrantMediaPermissions(User $user, array $permissions): User
    {
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh() ?? $user;
    }
}

if (! function_exists('driveAddS3Media')) {
    function driveAddS3Media(User $user, string $fileName = 'secret.pdf'): Media
    {
        return $user->addMedia(UploadedFile::fake()->create($fileName, 20, 'application/pdf'))
            ->toMediaCollection('uploads');
    }
}

if (! function_exists('driveAddPublicMedia')) {
    function driveAddPublicMedia(User $user, string $fileName = 'avatar.jpg'): Media
    {
        return $user->addMedia(UploadedFile::fake()->image($fileName))
            ->toMediaCollection('avatar');
    }
}
