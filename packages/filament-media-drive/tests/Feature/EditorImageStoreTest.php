<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mamenein\FilamentMediaDrive\Support\EditorImageStore;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
});

test('editor images land on the s3 media library uploads collection', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $media = EditorImageStore::store(UploadedFile::fake()->create('hero.png', 40, 'image/png'));

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->disk)->toBe('s3')
        ->and($media->collection_name)->toBe('uploads')
        ->and($media->file_name)->toBe('hero.png')
        ->and($media->model_type)->toBe($user->getMorphClass())
        ->and((int) $media->model_id)->toBe((int) $user->id)
        ->and((int) $media->getCustomProperty('user_id'))->toBe((int) $user->id);
});

test('editor image URL is the Spatie media URL for the stored file', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $media = EditorImageStore::store(UploadedFile::fake()->create('cdn.png', 40, 'image/png'));

    expect(EditorImageStore::url($media))->toBe($media->getUrl())
        ->and($media->disk)->toBe('s3');
});

test('livewire temporary uploads are stored from the livewire disk without passing the empty tmpfile handle', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $name = 'lw-hero.png';
    $disk = FileUploadConfiguration::disk();
    Storage::fake($disk);
    $key = FileUploadConfiguration::directory().'/'.$name;

    Storage::disk($disk)->put($key, $png);
    Storage::disk($disk)->put($key.'.json', json_encode([
        'name' => 'Hero Shot.png',
        'type' => 'image/png',
        'size' => strlen($png),
    ]));

    $media = EditorImageStore::store(TemporaryUploadedFile::createFromLivewire($name));

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->disk)->toBe('s3')
        ->and($media->collection_name)->toBe('uploads')
        ->and($media->file_name)->toBe('hero-shot.png')
        ->and(Storage::disk($disk)->exists($key))->toBeTrue();
});

test('editor image URL follows the s3 disk public URL including a CDN host', function () {
    $cdn = 'https://cdn.example.test/filament';
    config(['filesystems.disks.s3.url' => $cdn]);
    Storage::forgetDisk('s3');

    expect(Storage::disk('s3')->url('x'))->toContain('cdn.example.test');

    Storage::fake('s3');

    $user = User::factory()->create();
    $this->actingAs($user);

    $media = EditorImageStore::store(UploadedFile::fake()->create('cdn.png', 40, 'image/png'));

    expect(EditorImageStore::url($media))->toBe($media->getUrl());
});

test('explicit collection wins over the body/uploads default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $media = EditorImageStore::store(
        UploadedFile::fake()->create('thumb.png', 40, 'image/png'),
        $user,
        'uploads',
    );

    expect($media->collection_name)->toBe('uploads')
        ->and($media->file_name)->toBe('thumb.png');
});
