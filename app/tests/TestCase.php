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
        $this->loadPassportEnvKeys();

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
        $app['config']->set('passport.private_key', $_ENV['PASSPORT_PRIVATE_KEY']);
        $app['config']->set('passport.public_key', $_ENV['PASSPORT_PUBLIC_KEY']);

        if ($purge && $app->bound('db')) {
            $app['db']->purge();
        }
    }

    /**
     * Load fixture PEMs into PASSPORT_* before config boots.
     *
     * PHPUnit cannot store multiline env values cleanly; compose may also
     * leak empty PASSPORT_* vars. These fixtures are not production secrets.
     */
    protected function loadPassportEnvKeys(): void
    {
        $private = file_get_contents(__DIR__.'/Fixtures/oauth/oauth-private.key');
        $public = file_get_contents(__DIR__.'/Fixtures/oauth/oauth-public.key');

        $_ENV['PASSPORT_PRIVATE_KEY'] = $private;
        $_SERVER['PASSPORT_PRIVATE_KEY'] = $private;
        $_ENV['PASSPORT_PUBLIC_KEY'] = $public;
        $_SERVER['PASSPORT_PUBLIC_KEY'] = $public;
    }
}
