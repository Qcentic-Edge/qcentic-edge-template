<?php

namespace Mamenein\FilamentMediaDrive\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class EditorImageStore
{
    public const BODY_COLLECTION = 'body';

    public const FALLBACK_COLLECTION = 'uploads';

    /**
     * Livewire FileUpload state is a TemporaryUploadedFile (or a nested array).
     * Never pass that object into Spatie FileAdder without copyLivewireToLocalTemp.
     */
    public static function resolveUpload(mixed $value): UploadedFile|TemporaryUploadedFile
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if ($value instanceof TemporaryUploadedFile || $value instanceof UploadedFile) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return TemporaryUploadedFile::createFromLivewire($value);
        }

        throw new RuntimeException('No upload file was provided.');
    }

    public static function store(UploadedFile|TemporaryUploadedFile $file, mixed $record = null, ?string $collection = null): Media
    {
        $user = auth()->user();

        if (! $user instanceof HasMedia) {
            throw new RuntimeException('Authenticated user must implement Spatie HasMedia to store editor images.');
        }

        $model = self::targetModel($record, $user);
        $collection ??= self::collectionFor($model);
        $source = $file instanceof TemporaryUploadedFile
            ? self::copyLivewireToLocalTemp($file)
            : $file;

        return $model
            ->addMedia($source)
            ->usingFileName(self::safeFileName($file))
            ->withCustomProperties([
                'user_id' => $user->getAuthIdentifier(),
            ])
            ->toMediaCollection($collection, MediaDriveCatalog::DISK);
    }

    /**
     * Livewire TemporaryUploadedFile extends Symfony UploadedFile with an
     * empty tmpfile() parent path. Spatie FileAdder and addMediaFromDisk
     * (Flysystem writeStream + remote headers) both mis-handle that object
     * against the s3 disk. Always copy bytes out to a real local file.
     */
    private static function copyLivewireToLocalTemp(TemporaryUploadedFile $file): string
    {
        $ext = self::extension($file);
        $dest = sys_get_temp_dir().'/media-'.uniqid('', true).'.'.$ext;

        $stream = $file->readStream();

        if (is_resource($stream)) {
            $out = fopen($dest, 'w');

            if ($out === false) {
                throw new RuntimeException('Could not create a local temp file for an editor image.');
            }

            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        } else {
            $contents = $file->get();

            if (! is_string($contents) || $contents === '') {
                throw new RuntimeException('Editor image upload is empty.');
            }

            file_put_contents($dest, $contents);
        }

        if (! is_file($dest) || filesize($dest) === 0) {
            throw new RuntimeException('Editor image copy produced an empty file.');
        }

        return $dest;
    }

    private static function safeFileName(UploadedFile|TemporaryUploadedFile $file): string
    {
        $ext = self::extension($file);
        $base = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($base);

        if ($slug === '') {
            $slug = 'image';
        }

        return $slug.'.'.$ext;
    }

    private static function extension(UploadedFile|TemporaryUploadedFile $file): string
    {
        $fromName = strtolower((string) pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));
        $ext = $fromName !== '' ? $fromName : (string) $file->guessExtension();
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';

        return $ext;
    }

    public static function url(Media $media): string
    {
        return $media->getUrl();
    }

    private static function targetModel(mixed $record, HasMedia $user): HasMedia
    {
        if ($record instanceof HasMedia && $record instanceof Model && $record->exists) {
            return $record;
        }

        return $user;
    }

    private static function collectionFor(HasMedia $model): string
    {
        $hasBody = $model->getRegisteredMediaCollections()
            ->contains(fn (mixed $collection): bool => $collection->name === self::BODY_COLLECTION);

        return $hasBody ? self::BODY_COLLECTION : self::FALLBACK_COLLECTION;
    }
}
