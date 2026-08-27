<?php

/**
 * Media Library create + Drive ingest authorization.
 *
 * | Actor             | AuthN     | Create / ingest                                      |
 * | guest             | redirect  | —                                                    |
 * | user without perm | 403       | no Create:Media                                      |
 * | owner             | 200       | Spatie `uploads` row via EditorImageStore            |
 * | super_admin       | 200       | Spatie `uploads` row via EditorImageStore            |
 */

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use QcenticEdge\FilamentMediaDrive\Pages\DrivePage;
use QcenticEdge\FilamentMediaDrive\Support\EditorImageStore;
use QcenticEdge\FilamentMediaDrive\Support\MediaDriveCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

test('guest is redirected from media create and drive and cannot ingest', function () {
    asGuest();

    $this->get(MediaResource::getUrl('create', panel: 'app'))->assertRedirect();
    $this->get(MediaResource::getUrl('create', panel: 'admin'))->assertRedirect();
    $this->get(DrivePage::getUrl(panel: 'app'))->assertRedirect();
    $this->get(DrivePage::getUrl(panel: 'admin'))->assertRedirect();

    $file = UploadedFile::fake()->create('guest-drive.pdf', 20, 'application/pdf');

    expectGuestIngestAborts(fn () => app(DrivePage::class)->attach());
    expectGuestIngestAborts(fn () => app(DrivePage::class)->ingestUploadedFile($file));
    expectGuestIngestAborts(fn () => app(CreateMedia::class)->ingestUploadedFile(
        UploadedFile::fake()->create('guest-create.pdf', 20, 'application/pdf'),
    ));

    expect(Media::query()->whereIn('file_name', ['guest-drive.pdf', 'guest-create.pdf'])->exists())->toBeFalse();
});

test('user without Create:Media cannot open create or ingest', function () {
    $user = actingAsRole('user');
    driveGrantMediaPermissions($user, ['View:Media']);

    $this->get(MediaResource::getUrl('create', panel: 'app'))->assertForbidden();

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateMedia::class)->assertForbidden();

    $page = Livewire::test(DrivePage::class)->instance();

    expect(fn () => $page->ingestUploadedFile(UploadedFile::fake()->create('nope.pdf', 20, 'application/pdf')))
        ->toThrow(AuthorizationException::class);

    expect(Media::query()->where('file_name', 'nope.pdf')->exists())->toBeFalse();
});

test('owner with Create:Media stores an uploads row through EditorImageStore', function () {
    $owner = actingAsRole('user');
    driveGrantMediaPermissions($owner, ['View:Media', 'Create:Media']);

    $this->get(MediaResource::getUrl('create', panel: 'app'))->assertOk();

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateMedia::class)
        ->instance()
        ->ingestUploadedFile(UploadedFile::fake()->create('library.pdf', 20, 'application/pdf'));

    $created = Media::query()->where('file_name', 'library.pdf')->first();

    expect($created)->not->toBeNull()
        ->and($created->disk)->toBe('s3')
        ->and($created->collection_name)->toBe('uploads')
        ->and((int) $created->getCustomProperty('user_id'))->toBe((int) $owner->id);

    Livewire::test(DrivePage::class)
        ->instance()
        ->ingestUploadedFile(UploadedFile::fake()->create('drive.pdf', 20, 'application/pdf'));

    $attached = Media::query()->where('file_name', 'drive.pdf')->first();

    expect($attached)->not->toBeNull()
        ->and($attached->disk)->toBe('s3')
        ->and($attached->collection_name)->toBe('uploads')
        ->and((int) $attached->model_id)->toBe((int) $owner->id);

    $cataloged = EditorImageStore::store(UploadedFile::fake()->create('catalog.png', 40, 'image/png'));

    expect(MediaDriveCatalog::visibleTo($owner)->pluck('id'))->toContain($cataloged->id);

    $this->get(DrivePage::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('catalog.png')
        ->assertSee('library.pdf')
        ->assertSee('drive.pdf');
});

test('super_admin stores an uploads row through EditorImageStore', function () {
    $admin = actingAsRole('super_admin');

    $this->get(MediaResource::getUrl('create', panel: 'admin'))->assertOk();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateMedia::class)
        ->instance()
        ->ingestUploadedFile(UploadedFile::fake()->create('admin-library.pdf', 20, 'application/pdf'));

    Livewire::test(DrivePage::class)
        ->instance()
        ->ingestUploadedFile(UploadedFile::fake()->create('admin-drive.pdf', 20, 'application/pdf'));

    $library = Media::query()->where('file_name', 'admin-library.pdf')->first();
    $drive = Media::query()->where('file_name', 'admin-drive.pdf')->first();

    expect($library)->not->toBeNull()
        ->and($library->disk)->toBe('s3')
        ->and($library->collection_name)->toBe('uploads')
        ->and((int) $library->getCustomProperty('user_id'))->toBe((int) $admin->id)
        ->and($drive)->not->toBeNull()
        ->and($drive->disk)->toBe('s3')
        ->and($drive->collection_name)->toBe('uploads')
        ->and((int) $drive->model_id)->toBe((int) $admin->id);
});

function expectGuestIngestAborts(\Closure $callback): void
{
    try {
        $callback();
        test()->fail('guest ingest should abort');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
}
