<?php

namespace QcenticEdge\PluginUpdates\Access;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Who is allowed to see that a package is behind, and to bring it up to date.
 *
 * Lifted from the installer's Updates page rather than invented beside it:
 * super admin where the user model exposes roles, any authenticated panel user
 * on an app with no role package.
 *
 * It lives here rather than inside the notice because it is not a rendering
 * concern and not the notice's alone: two surfaces onto the same action must
 * not disagree about who may take it, so this is the one rule, and the
 * installer's page is expected to read it here too once it renders the report.
 */
final class Operator
{
    public static function check(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole('super_admin');
        }

        return true;
    }

    /** Whoever is signed in right now, if anyone. */
    public static function signedIn(): bool
    {
        return self::check(auth()->user());
    }
}
