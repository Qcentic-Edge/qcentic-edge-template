<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Illuminate\Foundation\Auth\User;

/**
 * A user model that exposes roles, as an app with a role package has. The
 * authorisation rule is super admin where this method exists, and any panel
 * user where it does not.
 */
class RoleAwareUser extends User
{
    public function __construct(private readonly bool $isSuperAdmin = true)
    {
        parent::__construct();
    }

    public function hasRole(string $role): bool
    {
        return $role === 'super_admin' && $this->isSuperAdmin;
    }
}
