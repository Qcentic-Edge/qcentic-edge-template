<?php

namespace QcenticEdge\FilamentMediaDrive\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use QcenticEdge\FilamentMediaDrive\Support\MediaDriveCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Field
{
    protected string $view = 'filament-media-drive::forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (): Closure {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                $media = Media::query()->find($value);

                if ($media === null || ! MediaDriveCatalog::canAttach(auth()->user(), $media)) {
                    $fail(__('filament-media-drive::drive.picker.forbidden'));
                }
            };
        });
    }

    /**
     * @return Collection<int, Media>
     */
    public function getVisibleMedia(): Collection
    {
        return MediaDriveCatalog::visibleTo(auth()->user());
    }
}
