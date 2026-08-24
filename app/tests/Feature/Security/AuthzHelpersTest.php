<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('authz helpers are defined and callable from pest', function () {
    expect(function_exists('seedUser'))->toBeTrue();
    expect(function_exists('seedSuperAdmin'))->toBeTrue();
    expect(function_exists('actingAsRole'))->toBeTrue();
    expect(function_exists('actingAsPassport'))->toBeTrue();
    expect(function_exists('asGuest'))->toBeTrue();
    expect(function_exists('assertForbiddenTo'))->toBeTrue();
    expect(function_exists('assertCannotTouchOthers'))->toBeTrue();

    $seededUser = seedUser();
    $seededSuperAdmin = seedSuperAdmin();

    expect($seededUser)->toBeInstanceOf(User::class);
    expect($seededSuperAdmin)->toBeInstanceOf(User::class);
    expect($seededUser->is($seededSuperAdmin))->toBeFalse();
    $this->assertGuest();

    $user = actingAsRole('user');
    expect($user)->toBeInstanceOf(User::class);
    $this->assertAuthenticatedAs($user);

    $superAdmin = actingAsRole('super_admin');
    expect($superAdmin)->toBeInstanceOf(User::class);
    $this->assertAuthenticatedAs($superAdmin);

    asGuest();
    $this->assertGuest();

    actingAsPassport($seededUser, ['read']);
    $this->assertAuthenticatedAs($seededUser, 'api');

    asGuest();
    $this->assertGuest();
    $this->assertGuest('api');

    Route::get('/__authz-forbidden', fn () => abort(403));
    assertForbiddenTo(fn () => $this->get('/__authz-forbidden'));

    $other = seedUser();
    actingAsRole('user');
    assertCannotTouchOthers($other);
});
