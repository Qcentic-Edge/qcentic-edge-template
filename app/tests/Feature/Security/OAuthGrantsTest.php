<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Spatie\Permission\Traits\HasRoles;

uses(DatabaseMigrations::class);

test('password grant is not registered and is rejected', function () {
    $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

    expect($provider)->not->toContain('enablePasswordGrant');
    expect(Passport::$passwordGrantEnabled)->toBeFalse();
    expect(config('passport.enable_password_grant'))->toBeFalsy();

    $this->postJson('/oauth/token', [
        'grant_type' => 'password',
        'client_id' => 'test-client',
        'client_secret' => 'secret',
        'username' => 'test@example.com',
        'password' => 'password',
        'scope' => '',
    ])
        ->assertStatus(400)
        ->assertJsonPath('error', 'unsupported_grant_type');
});

test('client credentials authorization code and refresh grants are enabled', function (string $grantType) {
    $response = $this->postJson('/oauth/token', [
        'grant_type' => $grantType,
    ]);

    expect($response->getStatusCode())->not->toBe(404);
    expect($response->json('error'))->not->toBe('unsupported_grant_type');
})->with([
    'client_credentials',
    'authorization_code',
    'refresh_token',
]);

test('user keeps HasRoles alongside Passport tokens', function () {
    $this->seed(RoleSeeder::class);

    expect(class_uses_recursive(User::class))->toContain(HasRoles::class);

    $user = User::factory()->create();
    $user->assignRole('user');

    expect($user->hasRole('user'))->toBeTrue();
});

test('api media route is not registered', function () {
    $this->getJson('/api/media')->assertNotFound();
});

test('panel login stays on the session web guard', function () {
    expect(config('auth.defaults.guard'))->toBe('web');
    expect(config('auth.guards.web.driver'))->toBe('session');
    expect(config('auth.guards.api.driver'))->toBe('passport');

    $this->get('/admin/login')->assertOk();
    $this->get('/app/login')->assertOk();
});
