<x-filament-panels::page>
    @if ($pending === [])
        <x-filament::section>
            <x-slot name="heading">Up to date</x-slot>
            <x-slot name="description">Every migration on disk has run against this database.</x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                When a plugin upgrade ships a migration, it appears here and this page offers to run it.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                {{ count($pending) }} pending {{ \Illuminate\Support\Str::plural('migration', count($pending)) }}
            </x-slot>
            <x-slot name="description">
                These have not run against this database yet.
            </x-slot>

            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($pending as $migration)
                    <li class="py-2 font-mono text-gray-700 dark:text-gray-200">{{ $migration }}</li>
                @endforeach
            </ul>

            <x-slot name="footerActions">
                {{ $this->runAction }}
            </x-slot>
        </x-filament::section>
    @endif

    @if (filled($output))
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Output</x-slot>

            <pre class="overflow-x-auto text-xs leading-5 text-gray-600 dark:text-gray-300">{{ $output }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
