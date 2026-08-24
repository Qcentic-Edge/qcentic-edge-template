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
     *
     * Pins also run in createApplication() so RefreshDatabase migrates sqlite
     * memory instead of the leaked container connection.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->pinTestEnvironment($app, purge: true);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pinTestEnvironment($this->app);
    }

    protected function pinTestEnvironment($app, bool $purge = false): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'log');
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('permission.cache.store', 'array');

        if ($purge && $app->bound('db')) {
            $app['db']->purge();
        }
    }
}
