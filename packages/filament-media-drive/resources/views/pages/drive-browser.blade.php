<div
    @class([
        'fi-media-drive-browser',
        'fi-media-drive-browser-grid grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4' => $this->viewMode === 'grid',
        'fi-media-drive-browser-list flex flex-col gap-2' => $this->viewMode === 'list',
    ])
    data-layout="{{ $this->viewMode }}"
>
    @forelse ($this->getItems() as $media)
        <article
            wire:key="drive-media-{{ $media->getKey() }}"
            class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
        >
            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                {{ $media->file_name }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $media->collection_name }}
            </p>
        </article>
    @empty
        <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament-media-drive::drive.empty') }}
        </p>
    @endforelse
</div>
