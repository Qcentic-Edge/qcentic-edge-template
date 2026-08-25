<?php

namespace Mamenein\FilamentInstaller\Tests\Feature;

use Mamenein\FilamentInstaller\Support\InstallerState;
use Mamenein\FilamentInstaller\Tests\TestCase;

class InstallerTest extends TestCase
{
    public function test_requests_redirect_to_installer_until_installed(): void
    {
        $this->get('/')->assertRedirect(url('install'));
    }

    public function test_installer_page_lists_checks(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('APP_KEY')
            ->assertSee('DB_CONNECTION')
            ->assertSee('Database reachable');
    }

    public function test_installer_works_before_sessions_table_exists(): void
    {
        // defineEnvironment sets SESSION_DRIVER=database (production default).
        // Boot must force cookie so StartSession does not 500 on missing table.
        $this->assertSame('cookie', config('session.driver'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('sessions'));

        $this->get('/install')->assertOk();
    }

    public function test_run_migrates_and_locks_the_installer(): void
    {
        config()->set('installer.create_user', false);

        $this->post('/install')->assertRedirect(url('/'));

        $this->assertTrue(InstallerState::isInstalled());

        $this->get('/')->assertOk()->assertSee('home');
        $this->get('/install')->assertNotFound();
        $this->post('/install')->assertNotFound();
    }

    public function test_run_creates_the_first_user(): void
    {
        config()->set('installer.user_model', \Mamenein\FilamentInstaller\Tests\Fixtures\User::class);

        \Illuminate\Support\Facades\Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $this->post('/install', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Str0ng!Password',
        ])->assertRedirect(url('/'));

        $user = \Mamenein\FilamentInstaller\Tests\Fixtures\User::query()->sole();

        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Str0ng!Password', $user->password));
        $this->assertTrue(InstallerState::isInstalled());
    }

    public function test_run_executes_configured_seeders_before_user(): void
    {
        config()->set('installer.create_user', false);
        config()->set('installer.seeders', [\Mamenein\FilamentInstaller\Tests\Fixtures\ProbeSeeder::class]);
        \Mamenein\FilamentInstaller\Tests\Fixtures\ProbeSeeder::$ran = false;

        $this->post('/install')->assertRedirect(url('/'));

        $this->assertTrue(\Mamenein\FilamentInstaller\Tests\Fixtures\ProbeSeeder::$ran);
        $this->assertTrue(InstallerState::isInstalled());
    }

    public function test_create_user_retries_same_email_after_partial_failure(): void
    {
        config()->set('installer.user_model', \Mamenein\FilamentInstaller\Tests\Fixtures\User::class);

        \Illuminate\Support\Facades\Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        \Mamenein\FilamentInstaller\Tests\Fixtures\User::query()->create([
            'name' => 'Old',
            'email' => 'admin@example.com',
            'password' => 'old',
        ]);

        $this->post('/install', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Str0ng!Password',
        ])->assertRedirect(url('/'));

        $user = \Mamenein\FilamentInstaller\Tests\Fixtures\User::query()->sole();

        $this->assertSame('Admin', $user->name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Str0ng!Password', $user->password));
    }

    public function test_run_rejects_weak_passwords(): void
    {
        foreach (['short', 'alllowercase1234', 'ALLUPPERCASE1234', 'NoDigitsOrSymbols!', 'N0SymbolsHere12'] as $weak) {
            $this->post('/install', [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => $weak,
            ])->assertSessionHasErrors(['password']);
        }

        $this->assertFalse(InstallerState::isInstalled());
    }

    public function test_run_validates_user_fields_when_creation_enabled(): void
    {
        $this->post('/install', [])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertFalse(InstallerState::isInstalled());
    }

    public function test_run_is_refused_while_checks_fail(): void
    {
        config()->set('installer.required_env', ['DEFINITELY_MISSING_ENV']);

        $this->post('/install')->assertRedirect();

        $this->assertFalse(InstallerState::isInstalled());
    }
}
