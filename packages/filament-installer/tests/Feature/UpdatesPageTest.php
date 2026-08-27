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
use QcenticEdge\PluginUpdates\PluginUpdates;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;

/**
 * The installer's Updates page, driven through Livewire — the second of the two
 * seams this effort agreed on, and the one an operator actually uses.
 *
 * Everything asserted here is what the operator can see or do: which packages
 * are listed, what each row says it owes, whether the row carries a button, and
 * what the database looks like after the button is pressed. Nothing here reaches
 * into the registry or the migration ledger; the page reads
 * `PluginUpdates::report()` and so does this test.
 */
class UpdatesPageTest extends PanelTestCase
{
    /** The installer, which registers itself and appears in its own list. */
    private const INSTALLER = 'qcentic-edge/filament-installer';

    /**
     * The identity the fixture packages borrow.
     *
     * A run refuses a package whose deployed version Composer cannot resolve,
     * so a fixture an operator could really click Update on has to be a package
     * Composer really knows. The update library is the one guaranteed to be
     * installed while this suite runs, and it registers nothing of its own, so
     * its name is free to stand in for a plugin.
     */
    private const BEHIND = 'qcentic-edge/plugin-updates';

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

    public function test_the_installer_appears_in_its_own_list(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();

        $status = PluginUpdates::report()->status(self::INSTALLER);

        $this->assertNotNull($status);
        $this->assertSame('Installer', $status->title);
        $this->assertSame(['installer_locks'], array_map(fn ($table) => $table->name, $status->tables()));
        $this->assertFalse($status->owesWork());

        $this->assertStringContainsString('Installer', $this->rowFor(self::INSTALLER));
    }

    public function test_a_package_that_owes_nothing_is_listed_with_no_update_button(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();

        $row = $this->rowFor(self::INSTALLER);

        $this->assertStringContainsString('Up to date', $row);
        $this->assertStringNotContainsString('Update', $row);
        $this->assertNull(Updates::getNavigationBadge());
    }

    public function test_the_page_says_so_when_everything_is_up_to_date(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();

        Livewire::test(Updates::class)
            ->assertOk()
            ->assertSee('Everything is up to date');
    }

    public function test_a_package_several_releases_behind_is_one_actionable_row_showing_the_gap(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();
        $this->registerBehindPackage();

        $status = PluginUpdates::report()->status(self::BEHIND);
        $this->assertSame(3, $status->versionsBehind());
        $this->assertCount(2, $status->pendingMigrations);

        $html = $this->renderPage();

        // One row for the package, not one per pending release, and not one per
        // pending migration.
        $this->assertSame(1, substr_count($html, $this->rowMarker(self::BEHIND)));

        $row = $this->rowFor(self::BEHIND, $html);
        $this->assertStringContainsString('3 releases behind', $row);
        $this->assertStringContainsString('Update', $row);
    }

    public function test_the_row_shows_the_versions_and_the_tables_the_work_will_touch(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();
        $this->registerBehindPackage();

        $row = $this->rowFor(self::BEHIND);

        // Stored version has never been recorded, code version is what Composer
        // resolved for the package the fixture borrows its identity from.
        $this->assertStringContainsString(PluginUpdates::package(self::BEHIND)->codeVersion(), $row);
        $this->assertStringContainsString('behind_widgets', $row);

        // The table does not exist yet, so its row count is an em dash, never a
        // zero — the pending schema work is what will create it.
        $this->assertStringNotContainsString('behind_widgets: 0', $row);
        $this->assertStringContainsString('behind_widgets: —', $row);
    }

    public function test_the_badge_counts_the_packages_needing_updates_and_clears_when_none_do(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();
        $this->registerBehindPackage();

        $this->assertSame('1', Updates::getNavigationBadge());

        Livewire::test(Updates::class)
            ->callAction('update', arguments: ['package' => self::BEHIND])
            ->assertHasNoActionErrors();

        $this->assertNull(Updates::getNavigationBadge());
    }

    public function test_the_button_updates_that_package_alone(): void
    {
        $this->asOperator();
        // Deliberately no migrate: the installer's own migration is unapplied,
        // so it owes work too and can be shown to be left alone.
        $this->registerBehindPackage();

        $this->assertTrue(PluginUpdates::report()->status(self::INSTALLER)->owesWork());

        Livewire::test(Updates::class)
            ->callAction('update', arguments: ['package' => self::BEHIND])
            ->assertHasNoActionErrors();

        $behind = PluginUpdates::report()->status(self::BEHIND);
        $this->assertFalse($behind->owesWork());
        $this->assertSame(PluginUpdates::package(self::BEHIND)->codeVersion(), $behind->storedVersion);
        $this->assertTrue(Schema::hasTable('behind_widgets'));

        // The other package is exactly where it was.
        $this->assertTrue(PluginUpdates::report()->status(self::INSTALLER)->owesWork());
        $this->assertFalse(Schema::hasTable(InstallerState::LOCK_TABLE));
    }

    public function test_a_package_that_owes_work_it_cannot_run_gets_the_reason_instead_of_a_button(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();

        PluginUpdates::register(
            UpdatablePackage::make('qcentic-edge/never-installed')
                ->title('Misdeclared Plugin')
                ->manifest(__DIR__.'/../Fixtures/BehindPackage/no-such-manifest.php'),
        );

        $row = $this->rowFor('qcentic-edge/never-installed');

        $this->assertStringContainsString('Needs attention', $row);
        $this->assertStringContainsString('cannot be updated', $row);
        $this->assertStringNotContainsString('>Update<', $row);
    }

    public function test_a_package_that_declared_no_tables_renders_without_one(): void
    {
        $this->asOperator();
        $this->applyEveryLoadedMigration();

        PluginUpdates::register(
            UpdatablePackage::make(self::BEHIND)
                ->title('Tableless Plugin')
                ->manifest(__DIR__.'/../Fixtures/BehindPackage/updates.php'),
        );

        $row = $this->rowFor(self::BEHIND);

        $this->assertStringContainsString('Tableless Plugin', $row);
        $this->assertStringContainsString('No tables', $row);
        $this->assertStringNotContainsString('—', $row);
    }

    private function asOperator(): void
    {
        $this->actingAs(User::create(['name' => 'Op', 'email' => 'op@example.test']));
    }

    /**
     * Everything the host has loaded — which is the installer's own migration
     * and nothing else. The fixture package's path is deliberately not loaded
     * into the application, so only an update run reaches it.
     */
    private function applyEveryLoadedMigration(): void
    {
        InstallerState::migrate();
    }

    private function registerBehindPackage(): void
    {
        PluginUpdates::register(
            UpdatablePackage::make(self::BEHIND)
                ->title('Behind Plugin')
                ->manifest(__DIR__.'/../Fixtures/BehindPackage/updates.php')
                ->migrations(__DIR__.'/../Fixtures/BehindPackage/migrations')
                ->tables(['behind_widgets', 'behind_notes']),
        );
    }

    private function renderPage(): string
    {
        return Livewire::test(Updates::class)->assertOk()->html();
    }

    /** How one package's row identifies itself in the rendered list. */
    private function rowMarker(string $package): string
    {
        return 'data-package="'.$package.'"';
    }

    /** The rendered row for one package, so a claim about it is a claim about that row. */
    private function rowFor(string $package, ?string $html = null): string
    {
        $html ??= $this->renderPage();

        $matched = preg_match(
            '/<tr[^>]*'.preg_quote($this->rowMarker($package), '/').'.*?<\/tr>/s',
            $html,
            $matches,
        );

        $this->assertSame(1, $matched, "No row rendered for [{$package}].");

        return $matches[0];
    }
}
