<?php

namespace QcenticEdge\PluginUpdates\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;

/**
 * The test application of a package that consumes this library for its
 * reporting and nothing else: the library's own provider, and not one thing
 * more.
 *
 * That is the ordinary case rather than an exotic one. Most first-party
 * packages take this library to declare what their releases owe; only a site
 * with a panel ever draws the notice. Package discovery is off as well as the
 * provider list being bare, so neither Filament nor Livewire can arrive by the
 * back door and make the assertions here accidentally true.
 *
 * Neither half of the notice's requirements is present here. For the host that
 * has one of them and not the other, see `LivewireOnlyTestCase`.
 */
abstract class PanellessTestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = false;

    protected function getPackageProviders($app): array
    {
        return [
            PluginUpdatesServiceProvider::class,
        ];
    }
}
