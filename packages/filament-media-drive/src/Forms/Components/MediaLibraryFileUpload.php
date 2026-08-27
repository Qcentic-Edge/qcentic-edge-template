<?php

namespace QcenticEdge\FilamentMediaDrive\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use QcenticEdge\FilamentMediaDrive\Support\EditorImageStore;
use QcenticEdge\FilamentMediaDrive\Support\MediaDriveCatalog;

class MediaLibraryFileUpload extends SpatieMediaLibraryFileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disk(MediaDriveCatalog::DISK);

        $this->saveUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if ($record === null) {
                return null;
            }

            $media = EditorImageStore::store($file, $record, $component->getCollection());

            return $media->getAttributeValue('uuid');
        });
    }
}
