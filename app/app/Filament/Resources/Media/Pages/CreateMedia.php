<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use QcenticEdge\FilamentMediaDrive\Support\EditorImageStore;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (! $user instanceof HasMedia) {
            abort(403);
        }

        Gate::authorize('create', Media::class);

        return EditorImageStore::store(
            EditorImageStore::resolveUpload($data['file'] ?? null),
            $user,
            EditorImageStore::FALLBACK_COLLECTION,
        );
    }

    public function ingestUploadedFile(mixed $raw): Model
    {
        return $this->handleRecordCreation(['file' => $raw]);
    }
}
