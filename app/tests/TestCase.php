<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pin the test environment in config, not env vars.
     *
     * Inside the dev container the compose env_file injects real environment
     * variables (DB_CONNECTION=libsql, SESSION_DRIVER=database, ...). PHPUnit's
     * <env force> does not overwrite $_SERVER, and Laravel's env() reads
     * $_SERVER first, so phpunit.xml alone cannot win that precedence fight.
     * Overriding config here makes tests hermetic in every environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.debug', false);
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
        $this->app['config']->set('session.driver', 'array');
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('queue.default', 'sync');
        $this->app['config']->set('mail.default', 'log');
    }
}
