<?php

namespace QcenticEdge\FilamentInstaller\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Stands in for an app using a role package: the Updates page asks hasRole()
 * when the user model offers it.
 */
class RoleUser extends Authenticatable
{
    protected $guarded = [];

    protected $table = 'users';

    /** @var list<string> */
    public array $roles = [];

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}
