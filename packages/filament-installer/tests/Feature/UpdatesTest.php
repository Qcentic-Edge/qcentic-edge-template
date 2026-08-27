<?php

namespace QcenticEdge\FilamentInstaller\Tests\Feature;

use QcenticEdge\FilamentInstaller\Support\InstallerState;
use QcenticEdge\FilamentInstaller\Tests\TestCase;

class UpdatesTest extends TestCase
{
    public function test_everything_on_disk_is_pending_before_the_first_migrate(): void
    {
        $pending = InstallerState::pendingMigrations();

        $this->assertContains('create_installer_locks_table', $pending);
    }

    public function test_nothing_is_pending_once_the_migrations_have_run(): void
    {
        InstallerState::migrate();

        $this->assertSame([], InstallerState::pendingMigrations());
    }

    public function test_a_migration_added_after_install_shows_up_as_pending(): void
    {
        InstallerState::migrate();
        $this->assertSame([], InstallerState::pendingMigrations());

        // Stand in for a plugin upgrade shipping a new migration.
        $dir = $this->app->databasePath('migrations');
        @mkdir($dir, 0777, true);
        $file = $dir.'/2099_01_01_000000_add_a_later_column.php';
        file_put_contents($file, '<?php return new class extends \Illuminate\Database\Migrations\Migration { public function up(): void {} };');

        try {
            $this->assertSame(
                ['2099_01_01_000000_add_a_later_column'],
                InstallerState::pendingMigrations(),
            );
        } finally {
            @unlink($file);
        }
    }

    public function test_the_pending_count_check_reflects_the_real_pending_list(): void
    {
        $before = collect(InstallerState::checks())
            ->firstWhere('label', 'Pending migrations');

        $this->assertSame(count(InstallerState::pendingMigrations()).' pending', $before['detail']);

        InstallerState::migrate();

        $after = collect(InstallerState::checks())
            ->firstWhere('label', 'Pending migrations');

        $this->assertSame('none pending', $after['detail']);
    }
}
