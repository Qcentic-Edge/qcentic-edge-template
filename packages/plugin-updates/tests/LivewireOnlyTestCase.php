<?php

namespace QcenticEdge\PluginUpdates\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;

/**
 * An application with Livewire and no Filament panel.
 *
 * The half-and-half host, and the one that used to fall through the guard. A
 * Laravel app with Livewire in it is ordinary — a package that uses Livewire
 * for its own screens, an app that predates its panel, a Filament install whose
 * providers are listed by hand — and none of that gives the library a topbar to
 * hang a notice on. Filament's own providers are absent here, and package
 * discovery is off so they cannot arrive by the back door and make the
 * assertions accidentally true.
 */
abstract class LivewireOnlyTestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = false;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PluginUpdatesServiceProvider::class,
        ];
    }
}
