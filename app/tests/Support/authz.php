<?php

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

/**
 * Seed a `user` role record. Attaches Spatie/Shield roles when HasRoles is present.
 */
function seedUser(): User
{
    return seedRoleUser('user');
}

/**
 * Seed a `super_admin` role record. Attaches Spatie/Shield roles when HasRoles is present.
 */
function seedSuperAdmin(): User
{
    return seedRoleUser('super_admin');
}

/**
 * Authenticate as a `user` or `super_admin`. Role attach is skipped until Shield/Spatie HasRoles lands.
 */
function actingAsRole(string $role): User
{
    $user = match ($role) {
        'user' => seedUser(),
        'super_admin' => seedSuperAdmin(),
        default => throw new InvalidArgumentException("Unknown role [{$role}]. Use user or super_admin."),
    };

    test()->actingAs($user);

    return $user;
}

/**
 * Authenticate for API tests. Uses Passport::actingAs when installed; otherwise Laravel actingAs($user, 'api').
 *
 * @param  list<string>  $scopes
 */
function actingAsPassport(Authenticatable $user, array $scopes = []): mixed
{
    if (class_exists(Passport::class)) {
        Passport::actingAs($user, $scopes);

        return test();
    }

    if (config('auth.guards.api') === null) {
        config()->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
    }

    return test()->actingAs($user, 'api');
}

/**
 * Clear the authenticated user (web by default).
 */
function asGuest(?string $guard = null): mixed
{
    if ($guard !== null) {
        return test()->actingAsGuest($guard);
    }

    foreach (array_keys(config('auth.guards', [])) as $name) {
        auth()->guard($name)->forgetUser();
    }

    auth()->shouldUse(config('auth.defaults.guard', 'web'));

    return test();
}

/**
 * Assert the action is forbidden (HTTP 403, or an AuthorizationException).
 *
 * @param  callable(): mixed  $action
 */
function assertForbiddenTo(callable $action): void
{
    try {
        $response = $action();
    } catch (AuthorizationException) {
        return;
    }

    if (! $response instanceof TestResponse) {
        throw new InvalidArgumentException('assertForbiddenTo expects the action to return a TestResponse.');
    }

    $response->assertForbidden();
}

/**
 * IDOR seam: the authenticated user must not be allowed to mutate another user’s record.
 */
function assertCannotTouchOthers(Model $record): void
{
    $actor = auth()->user();

    expect($actor)->not->toBeNull();

    if ($record instanceof Authenticatable) {
        expect($record->getAuthIdentifier())->not->toEqual($actor->getAuthIdentifier());
    }

    $ownerId = $record->getAttribute('user_id') ?? $record->getAttribute('owner_id');

    if ($ownerId !== null) {
        expect($ownerId)->not->toEqual($actor->getKey());
    }

    expect(Gate::forUser($actor)->denies('view', $record))->toBeTrue();
    expect(Gate::forUser($actor)->denies('update', $record))->toBeTrue();
    expect(Gate::forUser($actor)->denies('delete', $record))->toBeTrue();
}

function seedRoleUser(string $role): User
{
    $user = User::factory()->create();

    attachRoleIfAvailable($user, $role);

    return $user;
}

function attachRoleIfAvailable(User $user, string $role): void
{
    if (! method_exists($user, 'assignRole')) {
        return;
    }

    try {
        if (class_exists(Role::class)) {
            Role::findOrCreate($role, config('auth.defaults.guard', 'web'));
        }

        $user->assignRole($role);
    } catch (Throwable) {
        // Shield / Spatie permission tables are not installed yet.
    }
}
