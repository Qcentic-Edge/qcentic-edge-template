<?php

namespace Mamenein\FilamentInstaller\Tests;

use Mamenein\FilamentInstaller\FilamentInstallerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $lockFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFile = sys_get_temp_dir().'/installer-test-'.uniqid().'.lock';
        $key = 'base64:'.base64_encode(random_bytes(32));
        config()->set('app.key', $key);
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;
        putenv('DB_CONNECTION=testing');
        $_ENV['DB_CONNECTION'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'testing';
        config()->set('installer.lock_file', $this->lockFile);
        config()->set('installer.required_env', ['APP_KEY', 'DB_CONNECTION']);
        config()->set('database.default', 'testing');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        // Match production default so boot must override to cookie while unlocked.
        $app['config']->set('session.driver', 'database');
    }

    protected function getPackageProviders($app): array
    {
        return [FilamentInstallerServiceProvider::class];
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->get('/', fn () => 'home');
    }
}
