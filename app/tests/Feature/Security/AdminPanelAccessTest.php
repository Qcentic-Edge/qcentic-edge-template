<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\Models\Role;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user cannot open admin when admin is super_admin-only', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('super_admin can open admin', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('user can open app panel', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/app')
        ->assertOk();
});

test('super_admin can open app panel', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/app')
        ->assertOk();
});

test('database seeder creates super_admin and user roles', function () {
    expect(Role::query()->where('name', 'super_admin')->exists())->toBeTrue();
    expect(Role::query()->where('name', 'user')->exists())->toBeTrue();
});
