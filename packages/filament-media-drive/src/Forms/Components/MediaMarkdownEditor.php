<?php

namespace QcenticEdge\FilamentMediaDrive\Forms\Components;

use Filament\Forms\Components\MarkdownEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use QcenticEdge\FilamentMediaDrive\Support\EditorImageStore;
use QcenticEdge\FilamentMediaDrive\Support\MediaDriveCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaMarkdownEditor extends MarkdownEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileAttachmentsDisk(MediaDriveCatalog::DISK);

        $this->saveUploadedFileAttachmentUsing(function (TemporaryUploadedFile $file, mixed $record): Media {
            return EditorImageStore::store($file, $record);
        });

        $this->getFileAttachmentUrlUsing(function (mixed $file): ?string {
            if ($file instanceof Media) {
                return EditorImageStore::url($file);
            }

            if (is_string($file) && filter_var($file, FILTER_VALIDATE_URL)) {
                return $file;
            }

            return null;
        });
    }
}
