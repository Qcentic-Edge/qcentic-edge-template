# Filament Media Drive

First-party Filament 5 panel plugin: a Drive-style media browser (grid and list) plus a picker field. Files live in **Spatie Media Library** on the `s3` disk (MinIO locally, Bunny in production). The plugin does not add a second media table.

## Install

This template ships the package via a Composer path repository (`packages/filament-media-drive`). Register it on each panel:

```php
use QcenticEdge\FilamentMediaDrive\MediaDrivePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MediaDrivePlugin::make());
}
```

## Drive page

The Drive page is **not** in the sidebar — **Media Library** is the nav. `/drive` still exists for the picker, tests, and direct URL. It lists Spatie media on the `s3` disk. Layout toggles between grid and list. Authorization uses the host app's `MediaPolicy` (`viewAny` / `view`): guests are sent to login, users without media permission get 403, owners see their own files, and `super_admin` sees everything.

**Canonical upload path:** Media Library **create** (`CreateMedia`). That form stores new bytes through `EditorImageStore` onto the signed-in user’s `uploads` collection on `s3`. Drive header **Attach** uses the same store (Livewire `FileUpload` with `storeFiles(false)`). Do not leave Attach as a no-op while `canCreate()` is false. MediaPicker “attach” only selects an existing catalog row; it does not upload.

Never call `addMediaFromDisk` for these uploads, and never pass a Livewire `TemporaryUploadedFile` into Spatie `FileAdder` — copy bytes to a real local temp file first (`EditorImageStore::copyLivewireToLocalTemp` / `store()`). The Livewire object’s parent path is an empty `tmpfile()`, so Spatie would write an empty blob or throw `DiskCannotBeAccessed`.

## Editor images and collection uploads

`MediaMarkdownEditor` and `MediaRichEditor` share Filament’s `HasFileAttachments` trait and the same `EditorImageStore`. The attach-image toolbar still opens a local file picker, but the file is stored as Spatie media on the `s3` disk (same catalog as Drive). Inserted URLs use `$media->getUrl()`: the disk `url` / `AWS_URL` when a file CDN is set, otherwise the direct disk URL. Do not use stock `MarkdownEditor` / `RichEditor` on forms that can attach files, and do not use Filament’s official Spatie Media Library plugin as a rich-editor `fileAttachmentProvider`.

```php
use QcenticEdge\FilamentMediaDrive\Forms\Components\MediaMarkdownEditor;
use QcenticEdge\FilamentMediaDrive\Forms\Components\MediaRichEditor;

MediaMarkdownEditor::make('body')
MediaRichEditor::make('body')
```

If the form record already exists and registers a `body` media collection, files attach there. Otherwise they go on the authenticated user’s `uploads` collection.

Thumbnails and other Spatie collections should use `MediaLibraryFileUpload` instead of Filament’s stock `SpatieMediaLibraryFileUpload`. The stock field calls `addMediaFromString()` on a Livewire temp file, which MinIO/S3 rejects the same way (`DiskCannotBeAccessed`).

```php
use QcenticEdge\FilamentMediaDrive\Forms\Components\MediaLibraryFileUpload;

MediaLibraryFileUpload::make('thumbnail')
    ->collection('thumbnail')
    ->image()
```

## Picker field

Use the standalone picker on any form:

```php
use QcenticEdge\FilamentMediaDrive\Forms\Components\MediaPicker;

MediaPicker::make('mediaId')
```

The field only offers media the current user may `view`. Submitting another user's private media id is rejected.

## Tests

Pest tests live in this package (`tests/`). From the Laravel app:

```bash
php artisan test --testsuite=MediaDrive
```
