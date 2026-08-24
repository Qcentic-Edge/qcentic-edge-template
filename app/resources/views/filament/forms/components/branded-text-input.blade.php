<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ state: $wire.$entangle(@js($getStatePath())) }"
        {{
            $getExtraAttributeBag()->class([
                'fi-fo-branded-text-input flex overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-amber-500 dark:bg-white/5',
            ])
        }}
    >
        <span class="fi-fo-branded-text-input-mark inline-flex items-center bg-amber-500 px-2.5 text-sm font-semibold text-white">
            {{ $getBrandMark() }}
        </span>

        <input
            type="text"
            x-model="state"
            @if (filled($id = $getId())) id="{{ $id }}" @endif
            @if ($isDisabled()) disabled @endif
            @if ($isReadOnly()) readonly @endif
            @if ($isRequired()) required @endif
            @if (filled($placeholder = $getPlaceholder())) placeholder="{{ $placeholder }}" @endif
            @if (filled($maxLength = $getMaxLength())) maxlength="{{ $maxLength }}" @endif
            {{
                $getExtraInputAttributeBag()->class([
                    'fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none dark:text-white sm:text-sm',
                ])
            }}
        />
    </div>
</x-dynamic-component>
