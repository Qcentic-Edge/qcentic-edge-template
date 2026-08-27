{{--
    One row per registered plugin, the shape WordPress uses: what its database
    is at, what its code is at, what it owes, what the work will touch, and a
    button only where there is something to run.

    Every value here comes from `PluginUpdates::report()`. Nothing on this page
    reads a registry, a version ledger or a migrator, and nothing recomputes
    what the report already answered — `owesWork()` decides whether there is a
    button, and a version gap on its own never does.

    The layout is a <style> block with real class names rather than utility
    classes, which is how a package in this workstation styles a view it renders
    into somebody else's panel: the panel compiles its CSS from its own content
    paths, and a package's Blade file is not one of them.
--}}
@php
    $statuses = $this->statuses();
    $owing = array_filter($statuses, fn ($status) => $status->owesWork());
@endphp

<x-filament-panels::page>
    <style>
        .fi-installer-updates { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .fi-installer-updates th { text-align: start; font-weight: 600; padding: 0.5rem 0.75rem; opacity: 0.7; }
        .fi-installer-updates td { padding: 0.75rem; vertical-align: top; border-top: 1px solid rgb(128 128 128 / 0.2); }
        .fi-installer-updates-title { font-weight: 600; }
        .fi-installer-updates-name { display: block; opacity: 0.6; font-size: 0.75rem; }
        .fi-installer-updates-version { font-variant-numeric: tabular-nums; }
        .fi-installer-updates-quiet { opacity: 0.6; }
        .fi-installer-updates-owed { display: flex; flex-direction: column; gap: 0.25rem; }
        .fi-installer-updates-files { margin: 0; padding: 0; list-style: none; font-size: 0.75rem; opacity: 0.7; }
        .fi-installer-updates-tables { margin: 0; padding: 0; list-style: none; }
        .fi-installer-updates-scroll { overflow-x: auto; }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            @if ($owing === [])
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

        <div class="fi-installer-updates-scroll">
            <table class="fi-installer-updates">
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
                                <span class="fi-installer-updates-title">{{ $status->title }}</span>
                                <span class="fi-installer-updates-name">{{ $status->name }}</span>
                            </td>

                            <td class="fi-installer-updates-version">
                                {{ $status->storedVersion ?? 'not recorded' }}
                            </td>

                            <td class="fi-installer-updates-version">
                                {{ $status->codeVersion ?? 'unknown' }}
                            </td>

                            <td>
                                <div class="fi-installer-updates-owed">
                                    @if ($status->needsAttention())
                                        <strong>Needs attention</strong>
                                        {{-- Never null here: needing attention *is* owing work a run would refuse. --}}
                                        <span>{{ $status->unrunnableReason() }}</span>
                                    @elseif ($status->owesWork())
                                        {{-- One row for the whole gap, however many releases it spans. --}}
                                        @if ($status->versionsBehind() > 0)
                                            <strong>{{ $status->versionsBehind() }} {{ \Illuminate\Support\Str::plural('release', $status->versionsBehind()) }} behind</strong>
                                        @else
                                            <strong>Database update pending</strong>
                                        @endif

                                        @if ($status->schemaOwed())
                                            <span>{{ count($status->pendingMigrations) }} {{ \Illuminate\Support\Str::plural('migration', count($status->pendingMigrations)) }}</span>
                                            <ul class="fi-installer-updates-files">
                                                @foreach ($status->pendingMigrations as $migration)
                                                    <li>{{ $migration }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if ($status->seedOwed())
                                            <span>Seed data</span>
                                        @endif
                                    @else
                                        <span class="fi-installer-updates-quiet">Up to date</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if ($status->tables() === [])
                                    <span class="fi-installer-updates-quiet">No tables</span>
                                @else
                                    <ul class="fi-installer-updates-tables">
                                        @foreach ($status->tables() as $table)
                                            {{-- A table whose create-table migration has not run yet is absent, not empty. --}}
                                            <li>{{ $table->name }}: {{ $table->exists() ? number_format($table->rows) : '—' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>

                            <td>
                                @if ($status->owesWork() && $status->runnable())
                                    {{ ($this->updateAction)(['package' => $status->name]) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
