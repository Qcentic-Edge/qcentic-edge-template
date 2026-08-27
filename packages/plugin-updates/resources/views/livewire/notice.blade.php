{{--
    One badge per package that owes work, each naming itself, so that several
    packages owing work all surface and none masks another.

    Two shapes, because owing work and being able to do it are different
    questions: a package the report says is runnable gets the button, and one
    that owes work the library would refuse to run gets the reason instead.

    Three, counting the one that is not about a package at all: a report that
    could not be read is a single badge saying so, because an empty topbar and
    an unreadable report look identical from here.

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

    @php
        // The list first: reading it is what discovers whether the report could
        // be read at all, and `reportFailure()` answers for that attempt.
        $owing = $this->owing();
        $failure = $this->reportFailure();
    @endphp

    @if ($failure !== null)
        {{-- A topbar that went quiet would read as "every package is level", which
             is the one thing this library must never say about a database it could
             not ask. One badge, the reason in its tooltip, and no button: there is
             nothing here to run and nothing known to run it on. --}}
        <x-filament::badge
            color="danger"
            :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle"
            :tooltip="$failure"
            wire:key="plugin-updates-unreadable"
        >
            Update status is unavailable
        </x-filament::badge>
    @endif

    @foreach ($owing as $status)
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
                {{-- The report words this, so the installer's page and this badge
                     cannot describe the same gap differently. --}}
                :tooltip="$status->behindSummary()"
                :wire:key="'plugin-updates-behind-'.$status->name"
            >
                {{ $status->title }}
            </x-filament::badge>

            {{ ($this->updateAction)(['package' => $status->name]) }}
        @endif
    @endforeach

    <x-filament-actions::modals />
</div>
