<?php

namespace QcenticEdge\PluginUpdates\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use QcenticEdge\PluginUpdates\PluginUpdatesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    /**
     * The library depends on nothing but the framework, so the test app needs
     * nothing but the framework either.
     */
    protected function getPackageProviders($app): array
    {
        return [
            PluginUpdatesServiceProvider::class,
        ];
    }
}
