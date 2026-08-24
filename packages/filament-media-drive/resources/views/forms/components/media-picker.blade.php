<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ state: $wire.$entangle(@js($getStatePath())) }"
        class="fi-fo-media-picker flex flex-col gap-2"
    >
        @forelse ($getVisibleMedia() as $media)
            <button
                type="button"
                wire:key="picker-media-{{ $media->getKey() }}"
                @click="state = {{ $media->getKey() }}"
                @class([
                    'flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm ring-1 ring-gray-950/10 dark:ring-white/20',
                    'bg-amber-50 ring-amber-500 dark:bg-amber-500/10' => $getState() == $media->getKey(),
                ])
            >
                <span>{{ $media->file_name }}</span>
            </button>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-media-drive::drive.empty') }}
            </p>
        @endforelse
    </div>
</x-dynamic-component>
