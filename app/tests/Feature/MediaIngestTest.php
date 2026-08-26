<?php

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mamenein\FilamentMediaDrive\Pages\DrivePage;
use Mamenein\FilamentMediaDrive\Support\EditorImageStore;
use Mamenein\FilamentMediaDrive\Support\MediaDriveCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

test('media created via the store appears in the drive s3 catalog', function () {
    $owner = actingAsRole('user');
    driveGrantMediaPermissions($owner, ['View:Media', 'Create:Media']);

    $media = EditorImageStore::store(UploadedFile::fake()->create('catalog.png', 40, 'image/png'));

    expect($media->disk)->toBe('s3')
        ->and(MediaDriveCatalog::visibleTo($owner)->pluck('id'))->toContain($media->id);

    $this->get(DrivePage::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('catalog.png');
});

test('guest cannot open drive attach or media create', function () {
    asGuest();

    $this->get(DrivePage::getUrl(panel: 'app'))->assertRedirect();
    $this->get(MediaResource::getUrl('create'))->assertRedirect();
});

test('owner with Create:Media can create a library row through EditorImageStore', function () {
    $owner = actingAsRole('user');
    driveGrantMediaPermissions($owner, ['View:Media', 'Create:Media']);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateMedia::class)
        ->instance()
        ->ingestUploadedFile(UploadedFile::fake()->create('library.pdf', 20, 'application/pdf'));

    $media = Media::query()->where('file_name', 'library.pdf')->first();

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('s3')
        ->and($media->collection_name)->toBe('uploads')
        ->and((int) $media->getCustomProperty('user_id'))->toBe((int) $owner->id);
});

test('user without Create:Media cannot open media create', function () {
    $user = actingAsRole('user');
    driveGrantMediaPermissions($user, ['View:Media']);

    $this->get(MediaResource::getUrl('create'))->assertForbidden();
});
