<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

/**
 * A package declaring itself from its own service provider, exactly as a real
 * plugin does, so that "nothing runs on boot" can be asserted against an
 * application that has genuinely booted with one registered rather than against
 * a package a test registered afterwards.
 */
class BootRegisteringProvider extends ServiceProvider
{
    public function boot(): void
    {
        PluginUpdates::register(
            UpdatablePackage::make('qcentic-edge/plugin-updates')
                ->title('Run Plugin')
                ->manifest(__DIR__.'/RunPackage/updates.php')
                ->migrations(__DIR__.'/RunPackage/migrations')
                ->seeder(RunPackageSeeder::class),
        );
    }
}
