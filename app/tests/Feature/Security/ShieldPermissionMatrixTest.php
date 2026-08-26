<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(DatabaseMigrations::class);

test('role seeder generates Shield permissions for super_admin and user only', function () {
    $this->seed(RoleSeeder::class);

    $admin = Role::findByName('super_admin');
    $user = Role::findByName('user');

    expect(Permission::count())->toBeGreaterThan(0);
    expect($admin->permissions()->count())->toBe(Permission::count());

    $userNames = $user->permissions()->pluck('name');
    expect($userNames->diff(['View:ApiTokens'])->isEmpty())->toBeTrue();
});
