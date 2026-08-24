<?php

use App\Filament\Resources\Media\MediaResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

test('guest is redirected from the media panel and gets 401 on signed media urls', function () {
    $owner = seedUser();
    $private = addPrivateMedia($owner);
    $signed = signedMediaUrl($private);

    asGuest();

    $this->get(MediaResource::getUrl('index'))->assertRedirect();
    $this->get(MediaResource::getUrl('index', panel: 'app'))->assertRedirect();
    $this->getJson($signed)->assertUnauthorized();
    $this->patchJson(route('media.update', $private), ['name' => 'hacked'])->assertUnauthorized();
    $this->deleteJson(route('media.destroy', $private))->assertUnauthorized();
});

test('user without media permission cannot open the media library', function () {
    actingAsRole('user');

    $this->get(MediaResource::getUrl('index', panel: 'app'))->assertForbidden();
});

test('owner can view and mutate own public and private media', function () {
    $owner = actingAsRole('user');
    grantMediaPermissions($owner, ['View:Media', 'Update:Media', 'Delete:Media']);

    $public = addPublicMedia($owner);
    $private = addPrivateMedia($owner);

    $this->get(MediaResource::getUrl('view', ['record' => $public], panel: 'app'))->assertOk();
    $this->get(MediaResource::getUrl('view', ['record' => $private], panel: 'app'))->assertOk();
    $this->get(signedMediaUrl($private))->assertOk();

    $this->patchJson(route('media.update', $public), ['name' => 'public-own'])->assertNoContent();
    $this->patchJson(route('media.update', $private), ['name' => 'private-own'])->assertNoContent();

    expect($public->fresh()->name)->toBe('public-own');
    expect($private->fresh()->name)->toBe('private-own');

    $this->deleteJson(route('media.destroy', $private))->assertNoContent();
    expect(Media::query()->whereKey($private->getKey())->exists())->toBeFalse();
});

test('other user cannot show update delete or signed url of someone else private media', function () {
    $owner = seedUser();
    $private = addPrivateMedia($owner);

    $other = actingAsRole('user');
    grantMediaPermissions($other, ['View:Media', 'Update:Media', 'Delete:Media']);

    assertCannotTouchOthers($private);

    $this->get(MediaResource::getUrl('view', ['record' => $private], panel: 'app'))->assertForbidden();
    $this->patchJson(route('media.update', $private), ['name' => 'stolen'])->assertForbidden();
    $this->deleteJson(route('media.destroy', $private))->assertForbidden();
    $this->getJson(signedMediaUrl($private))->assertForbidden();

    expect(Media::query()->whereKey($private->getKey())->exists())->toBeTrue();
    expect($private->fresh()->name)->not->toBe('stolen');
});

test('user with view_any but not update_any can list media but cannot mutate others', function () {
    $owner = seedUser();
    $private = addPrivateMedia($owner);

    $viewer = actingAsRole('user');
    grantMediaPermissions($viewer, ['ViewAny:Media']);

    $this->get(MediaResource::getUrl('index', panel: 'app'))->assertOk();
    $this->get(MediaResource::getUrl('view', ['record' => $private], panel: 'app'))->assertOk();

    expect(Gate::forUser($viewer)->allows('view', $private))->toBeTrue();
    expect(Gate::forUser($viewer)->denies('update', $private))->toBeTrue();
    expect(Gate::forUser($viewer)->denies('delete', $private))->toBeTrue();

    $this->patchJson(route('media.update', $private), ['name' => 'mutated'])->assertForbidden();
    $this->deleteJson(route('media.destroy', $private))->assertForbidden();

    expect($private->fresh()->name)->not->toBe('mutated');
    expect(Media::query()->whereKey($private->getKey())->exists())->toBeTrue();
});

test('super_admin can access any media', function () {
    $owner = seedUser();
    $public = addPublicMedia($owner);
    $private = addPrivateMedia($owner);

    actingAsRole('super_admin');

    $this->get(MediaResource::getUrl('index'))->assertOk();
    $this->get(MediaResource::getUrl('view', ['record' => $public]))->assertOk();
    $this->get(MediaResource::getUrl('view', ['record' => $private]))->assertOk();
    $this->get(signedMediaUrl($private))->assertOk();
    $this->patchJson(route('media.update', $private), ['name' => 'admin-edit'])->assertNoContent();
    expect($private->fresh()->name)->toBe('admin-edit');
    $this->deleteJson(route('media.destroy', $public))->assertNoContent();
    expect(Media::query()->whereKey($public->getKey())->exists())->toBeFalse();
});

test('public media urls follow disk url shape and cannot be mutated by others without permission', function () {
    $owner = seedUser();
    $public = addPublicMedia($owner);

    expect($public->getUrl())->toContain('/storage');
    expect($public->disk)->toBe('public');

    $other = actingAsRole('user');
    grantMediaPermissions($other, ['View:Media', 'Update:Media', 'Delete:Media']);

    $this->patchJson(route('media.update', $public), ['name' => 'stolen-public'])->assertForbidden();
    $this->deleteJson(route('media.destroy', $public))->assertForbidden();

    expect($public->fresh()->name)->not->toBe('stolen-public');
    expect(Media::query()->whereKey($public->getKey())->exists())->toBeTrue();
});

/**
 * @param  list<string>  $permissions
 */
function grantMediaPermissions(User $user, array $permissions): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $user->givePermissionTo($permissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh() ?? $user;
}

function addPublicMedia(User $user): Media
{
    return $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');
}

function addPrivateMedia(User $user): Media
{
    return $user->addMedia(UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf'))
        ->toMediaCollection('uploads');
}

function signedMediaUrl(Media $media): string
{
    return URL::temporarySignedRoute('media.signed', now()->addMinutes(5), ['media' => $media]);
}
