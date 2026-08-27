<?php

use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use QcenticEdge\FilamentMediaDrive\Pages\DrivePage;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3');
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

test('drive page is not in the panel sidebar', function () {
    expect(DrivePage::shouldRegisterNavigation())->toBeFalse();
});

test('guest is redirected from the drive page', function () {
    asGuest();

    $this->get(DrivePage::getUrl(panel: 'app'))->assertRedirect();
    $this->get(DrivePage::getUrl(panel: 'admin'))->assertRedirect();
});

test('user without media permission cannot open the drive page', function () {
    actingAsRole('user');

    $this->get(DrivePage::getUrl(panel: 'app'))->assertForbidden();
});

test('owner can open drive and sees own s3 media in grid and list', function () {
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
        ->assertSet('viewMode', 'grid')
        ->assertSee('owner-secret.pdf')
        ->call('setLayout', 'list')
        ->assertSet('viewMode', 'list')
        ->assertSee('owner-secret.pdf');

    expect($own->disk)->toBe('s3');
});

test('other user cannot see someone else private s3 media on drive', function () {
    $owner = seedUser();
    driveAddS3Media($owner, 'other-secret.pdf');

    $other = actingAsRole('user');
    driveGrantMediaPermissions($other, ['View:Media']);
    driveAddS3Media($other, 'mine.pdf');

    $this->get(DrivePage::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('mine.pdf')
        ->assertDontSee('other-secret.pdf');
});

test('super_admin can open drive and see any s3 media', function () {
    $owner = seedUser();
    driveAddS3Media($owner, 'admin-can-see.pdf');

    actingAsRole('super_admin');

    $this->get(DrivePage::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('admin-can-see.pdf');
});
