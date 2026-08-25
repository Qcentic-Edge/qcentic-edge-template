# Filament Media Drive

First-party Filament 5 panel plugin: a Drive-style media browser (grid and list) plus a picker field. Files live in **Spatie Media Library** on the `s3` disk (MinIO locally, Bunny in production). The plugin does not add a second media table.

## Install

This template ships the package via a Composer path repository (`packages/filament-media-drive`). Register it on each panel:

```php
use Mamenein\FilamentMediaDrive\MediaDrivePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MediaDrivePlugin::make());
}
```

## Drive page

The Drive page is **not** in the sidebar — **Media Library** is the nav. `/drive` still exists for the picker, tests, and direct URL. It lists Spatie media on the `s3` disk. Layout toggles between grid and list. Authorization uses the host app's `MediaPolicy` (`viewAny` / `view`): guests are sent to login, users without media permission get 403, owners see their own files, and `super_admin` sees everything.

## Markdown editor images

`MediaMarkdownEditor` extends Filament’s markdown field. The attach-image toolbar still opens a local file picker, but the file is stored as Spatie media on the `s3` disk (same catalog as Drive). The markdown `![](url)` uses `$media->getUrl()`: the disk `url` / `AWS_URL` when a file CDN is set, otherwise the direct disk URL.

```php
use Mamenein\FilamentMediaDrive\Forms\Components\MediaMarkdownEditor;

MediaMarkdownEditor::make('body')
```

If the form record already exists and registers a `body` media collection, files attach there. Otherwise they go on the authenticated user’s `uploads` collection.

Thumbnails and other Spatie collections should use `MediaLibraryFileUpload` instead of Filament’s stock `SpatieMediaLibraryFileUpload`. The stock field calls `addMediaFromString()` on a Livewire temp file, which MinIO/S3 rejects the same way (`DiskCannotBeAccessed`).

```php
use Mamenein\FilamentMediaDrive\Forms\Components\MediaLibraryFileUpload;

MediaLibraryFileUpload::make('thumbnail')
    ->collection('thumbnail')
    ->image()
```

## Picker field

Use the standalone picker on any form:

```php
use Mamenein\FilamentMediaDrive\Forms\Components\MediaPicker;

MediaPicker::make('mediaId')
```

The field only offers media the current user may `view`. Submitting another user's private media id is rejected.

## Tests

Pest tests live in this package (`tests/`). From the Laravel app:

```bash
php artisan test --testsuite=MediaDrive
```
