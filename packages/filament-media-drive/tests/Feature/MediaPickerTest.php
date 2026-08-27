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

test('owner can attach own s3 media with the picker', function () {
    $owner = actingAsRole('user');
    driveGrantMediaPermissions($owner, ['View:Media']);
    $own = driveAddS3Media($owner, 'attach-me.pdf');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(DrivePage::class)
        ->fillForm(['mediaId' => $own->getKey()])
        ->call('attach')
        ->assertHasNoFormErrors();
});

test('picker cannot attach others private media', function () {
    $owner = seedUser();
    $private = driveAddS3Media($owner, 'private-other.pdf');

    $other = actingAsRole('user');
    driveGrantMediaPermissions($other, ['View:Media', 'Create:Media']);
    driveAddS3Media($other, 'own.pdf');

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(DrivePage::class)
        ->fillForm(['mediaId' => $private->getKey()])
        ->call('attach')
        ->assertHasFormErrors(['mediaId']);
});

test('super_admin can attach others private media with the picker', function () {
    $owner = seedUser();
    $private = driveAddS3Media($owner, 'admin-attach.pdf');

    actingAsRole('super_admin');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(DrivePage::class)
        ->fillForm(['mediaId' => $private->getKey()])
        ->call('attach')
        ->assertHasNoFormErrors();
});
