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

Open **Drive** in the panel. The page lists Spatie media stored on the `s3` disk. Layout toggles between grid and list. Authorization uses the host app's `MediaPolicy` (`viewAny` / `view`): guests are sent to login, users without media permission get 403, owners see their own files, and `super_admin` sees everything.

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
