{{--
    One row per registered plugin, the shape WordPress uses: what its database
    is at, what its code is at, what it owes, what the work will touch, and a
    button only where there is something to run.

    Every value here comes from `PluginUpdates::report()`. Nothing on this page
    reads a registry, a version ledger or a migrator, and the only thing derived
    here rather than asked is which of the already-read statuses owe work, which
    the page does in `owing()` with the report's own predicate.

    The layout is a <style> block with real class names rather than utility
    classes, which is how a package in this workstation styles a view it renders
    into somebody else's panel — see filament-seo's social-preview and
    filament-admin-bar's bar, whose naming and formatting this follows. The
    panel compiles its CSS from its own content paths, and a package's Blade
    file is not one of them, so a utility class here would be a class name with
    no rule behind it on most sites.
--}}
@php
    // The list first: reading it is what discovers whether the report could be
    // read at all, and `reportFailure()` answers for that attempt.
    $statuses = $this->statuses();
    $failure = $this->reportFailure();
    $owing = $this->owing($statuses);
@endphp

<x-filament-panels::page>
    <style>
        .filament-installer-updates {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .filament-installer-updates th {
            padding: 0.5rem 0.75rem;
            opacity: 0.7;
            text-align: start;
            font-weight: 600;
        }

        .filament-installer-updates td {
            padding: 0.75rem;
            border-top: 1px solid rgb(128 128 128 / 0.2);
            vertical-align: top;
        }

        .filament-installer-updates__title {
            font-weight: 600;
        }

        .filament-installer-updates__name {
            display: block;
            opacity: 0.6;
            font-size: 0.75rem;
        }

        .filament-installer-updates__version {
            font-variant-numeric: tabular-nums;
        }

        .filament-installer-updates__quiet {
            opacity: 0.6;
        }

        .filament-installer-updates__owed {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filament-installer-updates__files {
            margin: 0;
            padding: 0;
            opacity: 0.7;
            list-style: none;
            font-size: 0.75rem;
        }

        .filament-installer-updates__tables {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .filament-installer-updates__scroll {
            overflow-x: auto;
        }

        .filament-installer-updates__problem {
            margin: 0;
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            @if ($failure !== null)
                Update status is unavailable
            @elseif ($owing === [])
                Everything is up to date
            @else
                {{ count($owing) }} {{ \Illuminate\Support\Str::plural('plugin', count($owing)) }}
                {{ count($owing) === 1 ? 'needs' : 'need' }} a database update
            @endif
        </x-slot>
        <x-slot name="description">
            Code arrives by redeploy; the database step happens here. Each plugin is updated on its own,
            and nothing here stops two operators running the same update at once.
        </x-slot>

        @if ($failure !== null)
            {{-- An empty list and an unreadable report look identical from here,
                 so the reason is shown rather than a page that quietly claims
                 every plugin is fine. --}}
            <p class="filament-installer-updates__problem">
                What each plugin owes could not be read, so nothing below can be trusted to be complete:
                {{ $failure }}
            </p>
        @else
            <div class="filament-installer-updates__scroll">
                <table class="filament-installer-updates">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Database</th>
                            <th>Code</th>
                            <th>Owed</th>
                            <th>Tables</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statuses as $status)
                            <tr data-package="{{ $status->name }}" wire:key="installer-updates-{{ $status->name }}">
                                <td>
                                    <span class="filament-installer-updates__title">{{ $status->title }}</span>
                                    <span class="filament-installer-updates__name">{{ $status->name }}</span>
                                </td>

                                <td class="filament-installer-updates__version">
                                    {{ $status->storedVersion ?? 'not recorded' }}
                                </td>

                                <td class="filament-installer-updates__version">
                                    {{ $status->codeVersion ?? 'unknown' }}
                                </td>

                                <td>
                                    <div class="filament-installer-updates__owed">
                                        @if ($status->needsAttention())
                                            <strong>Needs attention</strong>
                                            {{-- Never null here: needing attention *is* owing work a run would refuse. --}}
                                            <span>{{ $status->unrunnableReason() }}</span>
                                        @elseif ($status->owesWork())
                                            {{-- One row for the whole gap, however many releases it spans,
                                                 and the report words it: the library's own topbar notice
                                                 renders the same sentence from the same method. --}}
                                            <strong>{{ $status->behindSummary() }}</strong>

                                            @if ($status->schemaOwed())
                                                <span>{{ count($status->pendingMigrations) }} {{ \Illuminate\Support\Str::plural('migration', count($status->pendingMigrations)) }}</span>
                                                <ul class="filament-installer-updates__files">
                                                    @foreach ($status->pendingMigrations as $migration)
                                                        <li>{{ $migration }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif

                                            @if ($status->seedOwed())
                                                <span>Seed data</span>
                                            @endif
                                        @else
                                            <span class="filament-installer-updates__quiet">Up to date</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    @if ($status->tables() === [])
                                        <span class="filament-installer-updates__quiet">No tables</span>
                                    @else
                                        <ul class="filament-installer-updates__tables">
                                            @foreach ($status->tables() as $table)
                                                {{-- A table whose create-table migration has not run yet is absent, not empty. --}}
                                                <li>{{ $table->name }}: {{ $table->exists() ? number_format($table->rows) : '—' }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>

                                <td>
                                    {{-- The same three states as the cell before it, spelled the same way:
                                         this is the owing branch that does *not* need attention, which is
                                         exactly the branch a run would go ahead for. --}}
                                    @if ($status->owesWork() && ! $status->needsAttention())
                                        {{ ($this->updateAction)(['package' => $status->name]) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
