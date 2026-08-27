<?php

namespace QcenticEdge\FilamentInstaller\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use QcenticEdge\FilamentInstaller\Filament\Pages\Updates;
use QcenticEdge\FilamentInstaller\Support\InstallerState;
use QcenticEdge\FilamentInstaller\Tests\Fixtures\RoleUser;
use QcenticEdge\FilamentInstaller\Tests\Fixtures\User;
use QcenticEdge\FilamentInstaller\Tests\PanelTestCase;

class UpdatesPageTest extends PanelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    public function test_a_guest_cannot_reach_the_page(): void
    {
        $this->assertFalse(Updates::canAccess());
    }

    public function test_an_authenticated_user_can_reach_it_when_the_app_has_no_roles(): void
    {
        $this->actingAs(User::create(['name' => 'Op', 'email' => 'op@example.test']));

        $this->assertTrue(Updates::canAccess());
    }

    public function test_only_a_super_admin_can_reach_it_when_the_app_has_roles(): void
    {
        $editor = RoleUser::create(['name' => 'Ed', 'email' => 'ed@example.test']);
        $editor->roles = ['editor'];
        $this->actingAs($editor);
        $this->assertFalse(Updates::canAccess());

        $admin = RoleUser::create(['name' => 'Su', 'email' => 'su@example.test']);
        $admin->roles = ['super_admin'];
        $this->actingAs($admin);
        $this->assertTrue(Updates::canAccess());
    }

    public function test_the_page_lists_pending_migrations_and_badges_the_count(): void
    {
        $this->actingAs(User::create(['name' => 'Op', 'email' => 'op@example.test']));

        $pending = InstallerState::pendingMigrations();
        $this->assertNotEmpty($pending);
        $this->assertSame((string) count($pending), Updates::getNavigationBadge());

        Livewire::test(Updates::class)
            ->assertOk()
            ->assertSee('create_installer_locks_table');
    }

    public function test_running_the_action_migrates_and_clears_the_list(): void
    {
        $this->actingAs(User::create(['name' => 'Op', 'email' => 'op@example.test']));

        Livewire::test(Updates::class)
            ->callAction('run')
            ->assertHasNoActionErrors();

        $this->assertSame([], InstallerState::pendingMigrations());
        $this->assertNull(Updates::getNavigationBadge());

        Livewire::test(Updates::class)
            ->assertOk()
            ->assertSee('Up to date');
    }
}
