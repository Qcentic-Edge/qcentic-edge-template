<?php

/**
 * Panel kit authorization recipe.
 *
 * Copy this file when adding a Filament resource (or a custom Field, widget, or plugin page).
 * Name the new file after the module. Keep this actor shape:
 *
 * | Actor             | AuthN            | AuthZ module | AuthZ entity                    |
 * | guest             | redirect / 401   | —            | —                               |
 * | user without perm | 200 login        | 403          | —                               |
 * | owner             | 200              | 200 own      | 403 others' private             |
 * | super_admin       | 200              | 200          | 200                             |
 *
 * Helpers (`tests/Support/authz.php`): actingAsRole(), asGuest(), seedUser(),
 * seedSuperAdmin(), assertForbiddenTo(), assertCannotTouchOthers().
 *
 * Surfaces proven here — not a tickets/kanban module:
 * 1. Custom Field — BrandedTextInput on UserResource
 * 2. Chart widget — CountsChart
 * 3. Drive plugin — DrivePage + MediaPicker
 */

use App\Filament\Forms\Components\BrandedTextInput;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\CountsChart;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mamenein\FilamentMediaDrive\Pages\DrivePage;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

// --- Custom Field: BrandedTextInput on UserResource ---

test('guest is redirected from the user form that hosts BrandedTextInput', function () {
    $target = seedUser();

    asGuest();

    $this->get(UserResource::getUrl('edit', ['record' => $target]))->assertRedirect();
});

test('user without permission cannot open own or others user form', function () {
    $own = actingAsRole('user');
    $other = seedUser();

    Livewire::test(EditUser::class, ['record' => $own->getRouteKey()])
        ->assertForbidden();

    Livewire::test(EditUser::class, ['record' => $other->getRouteKey()])
        ->assertForbidden();
});

test('super_admin can view BrandedTextInput and save a headline on another user', function () {
    $owner = seedUser();

    actingAsRole('super_admin');

    Livewire::test(EditUser::class, ['record' => $owner->getRouteKey()])
        ->assertSuccessful()
        ->assertFormFieldExists(
            'headline',
            fn ($field): bool => $field instanceof BrandedTextInput,
        )
        ->fillForm([
            'headline' => 'Staff engineer',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($owner->fresh()->headline)->toBe('Staff engineer');
});

// --- Chart widget: CountsChart ---

test('guest is forbidden from the counts chart widget', function () {
    asGuest();

    Livewire::test(CountsChart::class)
        ->assertForbidden();
});

test('user without super_admin cannot view the counts chart widget', function () {
    actingAsRole('user');

    Livewire::test(CountsChart::class)
        ->assertForbidden();
});

test('owner media counts do not include other users private media', function () {
    $owner = seedUser();
    driveAddS3Media($owner, 'owner-secret.pdf');
    driveAddS3Media($owner, 'owner-other.pdf');

    $viewer = seedUser();
    driveGrantMediaPermissions($viewer, ['View:Media']);

    expect(CountsChart::countsFor($viewer)['media'])->toBe(0);

    driveAddS3Media($viewer, 'mine.pdf');

    expect(CountsChart::countsFor($viewer)['media'])->toBe(1);
});

test('super_admin can view the counts chart and sees all media', function () {
    $owner = seedUser();
    driveAddS3Media($owner, 'a.pdf');
    driveAddS3Media($owner, 'b.pdf');
    driveAddS3Media($owner, 'c.pdf');

    $admin = actingAsRole('super_admin');

    Livewire::test(CountsChart::class)
        ->assertSuccessful()
        ->assertSee('Users and media');

    expect(CountsChart::countsFor($admin)['media'])->toBe(3);
});

// --- Drive plugin: DrivePage + MediaPicker ---

test('guest is redirected from the drive page', function () {
    asGuest();

    $this->get(DrivePage::getUrl(panel: 'app'))->assertRedirect();
    $this->get(DrivePage::getUrl(panel: 'admin'))->assertRedirect();
});

test('user without media permission cannot open the drive page', function () {
    actingAsRole('user');

    $this->get(DrivePage::getUrl(panel: 'app'))->assertForbidden();
});

test('owner can open drive, see own s3 media, and attach it with the picker', function () {
    $owner = actingAsRole('user');
    driveGrantMediaPermissions($owner, ['View:Media']);
    $own = driveAddS3Media($owner, 'owner-secret.pdf');
    driveAddPublicMedia($owner, 'avatar.jpg');

    $this->get(DrivePage::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('owner-secret.pdf')
        ->assertDontSee('avatar.jpg');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(DrivePage::class)
        ->assertSuccessful()
        ->assertSee('owner-secret.pdf')
        ->fillForm(['mediaId' => $own->getKey()])
        ->call('attach')
        ->assertHasNoFormErrors();
});

test('other user cannot see or attach someone else private s3 media', function () {
    $owner = seedUser();
    $private = driveAddS3Media($owner, 'other-secret.pdf');

    $other = actingAsRole('user');
    driveGrantMediaPermissions($other, ['View:Media', 'Create:Media']);
    driveAddS3Media($other, 'mine.pdf');

    assertCannotTouchOthers($private);

    $this->get(DrivePage::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('mine.pdf')
        ->assertDontSee('other-secret.pdf');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(DrivePage::class)
        ->fillForm(['mediaId' => $private->getKey()])
        ->call('attach')
        ->assertHasFormErrors(['mediaId']);
});

test('super_admin can open drive, see any s3 media, and attach it', function () {
    $owner = seedUser();
    $private = driveAddS3Media($owner, 'admin-can-see.pdf');

    actingAsRole('super_admin');

    $this->get(DrivePage::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('admin-can-see.pdf');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(DrivePage::class)
        ->fillForm(['mediaId' => $private->getKey()])
        ->call('attach')
        ->assertHasNoFormErrors();
});
