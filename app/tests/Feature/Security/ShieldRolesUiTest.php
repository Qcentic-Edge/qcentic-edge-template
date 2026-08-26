<?php

use App\Filament\Resources\Users\Pages\EditUser;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('super_admin can open Shield roles on admin', function () {
    actingAsRole('super_admin');

    $this->get(RoleResource::getUrl('index', panel: 'admin'))->assertOk();
});

test('user edit form has a roles field on admin', function () {
    $target = seedUser();

    actingAsRole('super_admin');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertSuccessful()
        ->assertFormFieldExists('roles');
});
