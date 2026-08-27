<?php

namespace QcenticEdge\PluginUpdates\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

/**
 * An application where the library is on the autoloader but its service
 * provider never registered — a Composer install that skipped
 * `package:discover`, or a host that lists its providers by hand and left this
 * one out.
 *
 * Nothing is registered here at all, which is exactly the point: the classes
 * resolve, so every call looks like it works, and the only thing missing is the
 * binding that makes a declaration go anywhere.
 */
abstract class ProviderlessTestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = false;

    protected function getPackageProviders($app): array
    {
        return [];
    }
}
