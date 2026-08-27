{{--
    One badge per package that owes work, each naming itself, so that several
    packages owing work all surface and none masks another.

    Two shapes, because owing work and being able to do it are different
    questions: a package the report says is runnable gets the button, and one
    that owes work the library would refuse to run gets the reason instead.

    The layout is a <style> block with real classes rather than utility classes,
    which is how a package in this workstation styles a view it renders into
    somebody else's panel — see filament-seo's social-preview and
    filament-admin-bar's bar. The panel compiles its own CSS from its own
    content paths, and a library's Blade file is not one of them, so a utility
    class here would be a class name with no rule behind it on most sites.
--}}
<div class="fi-plugin-updates-notice">
    <style>
        .fi-plugin-updates-notice {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }
    </style>

    @foreach ($this->owing() as $status)
        @if ($status->needsAttention())
            <x-filament::badge
                color="danger"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle"
                :tooltip="$status->unrunnableReason()"
                :wire:key="'plugin-updates-attention-'.$status->name"
            >
                {{ $status->title }} needs attention
            </x-filament::badge>
        @else
            <x-filament::badge
                color="warning"
                :tooltip="$status->versionsBehind() > 0
                    ? $status->versionsBehind().' '.str('release')->plural($status->versionsBehind()).' behind'
                    : 'Database update pending'"
                :wire:key="'plugin-updates-behind-'.$status->name"
            >
                {{ $status->title }}
            </x-filament::badge>

            {{ ($this->updateAction)(['package' => $status->name]) }}
        @endif
    @endforeach

    <x-filament-actions::modals />
</div>
