<?php

use App\Filament\Resources\Media\MediaResource;
use App\Models\User;
use App\Policies\MediaPolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
});

test('user has avatar and uploads media collections', function () {
    $user = User::factory()->create();

    $names = $user->getRegisteredMediaCollections()->pluck('name');

    expect($names)->toContain('avatar', 'uploads');

    $avatar = $user->getMediaCollection('avatar');
    expect($avatar->singleFile)->toBeTrue();
    expect($avatar->diskName)->toBe('public');

    $uploads = $user->getMediaCollection('uploads');
    expect($uploads->singleFile)->toBeFalse();
    expect($uploads->diskName)->toBe('s3');
});

test('user can attach media to the avatar collection', function () {
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    expect($user->getMedia('avatar'))->toHaveCount(1);
    expect($user->getFirstMedia('avatar')?->file_name)->toBe('avatar.jpg');
    expect($user->getFirstMedia('avatar')?->disk)->toBe('public');
});

test('avatar collection keeps a single file', function () {
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('first.jpg'))
        ->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->image('second.jpg'))
        ->toMediaCollection('avatar');

    expect($user->getMedia('avatar'))->toHaveCount(1);
    expect($user->getFirstMedia('avatar')?->file_name)->toBe('second.jpg');
});

test('user can attach multiple media to the uploads collection', function () {
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->create('one.pdf', 20, 'application/pdf'))
        ->toMediaCollection('uploads');
    $user->addMedia(UploadedFile::fake()->create('two.pdf', 20, 'application/pdf'))
        ->toMediaCollection('uploads');

    expect($user->getMedia('uploads'))->toHaveCount(2);
    expect($user->getFirstMedia('uploads')?->disk)->toBe('s3');
});

test('attaching media does not dispatch conversion jobs', function () {
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf'))
        ->toMediaCollection('uploads');

    Queue::assertNotPushed(PerformConversionsJob::class);
    expect($user->getFirstMedia('avatar')?->generated_conversions)->toBeEmpty();
});

test('media policy is registered for the spatie media model', function () {
    expect(Gate::getPolicyFor(Media::class))->toBeInstanceOf(MediaPolicy::class);
});

test('super_admin can open the filament media library', function () {
    $admin = actingAsRole('super_admin');

    $this->get(MediaResource::getUrl('index'))
        ->assertOk();

    expect($admin->hasRole('super_admin'))->toBeTrue();
});
