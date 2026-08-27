<?php

namespace QcenticEdge\PluginUpdates\Registry;

use RuntimeException;

/**
 * A registration that cannot reach the shared registry, because the library's
 * own service provider never registered — a Composer install that skipped
 * `package:discover`, or an application that lists its providers by hand and
 * left this one out.
 *
 * Silence here is the worst failure the library has. Nothing about the call
 * looks wrong: `app(PackageRegistry::class)` has a concrete class to auto-wire,
 * so it hands back a throwaway registry with no binding behind it, the
 * declaration lands in it, the object is discarded at the end of the call, and
 * every package then reports as owing nothing. A stale database read as healthy
 * is precisely the drift the whole design exists to prevent, and it is worse
 * than the missing manifest `IncompleteDeclaration` already refuses, because it
 * takes every package down with it rather than one.
 *
 * So it fails where the mistake is, at the moment a package declares itself,
 * naming what is wrong and what to do about it — the same shape as an
 * undeclared manifest, for the same reason.
 *
 * Distinct, deliberately, from a host that simply has no Livewire to render the
 * topbar notice with. That one is a quiet skip: the notice is an optional
 * surface, and a package consuming this library for reporting alone is entitled
 * to an application with no panel in it. A missing registry is not optional,
 * and the two must never be answered the same way.
 */
final class UnreachableRegistry extends RuntimeException
{
    public static function providerNotRegistered(string $provider): self
    {
        return new self(
            "The package registry is unreachable because [{$provider}] is not registered, so a "
            .'declaration would be written to a throwaway registry and lost, and every package '
            .'would then report its database as up to date. Run `composer dump-autoload` so '
            .'package discovery registers it, or add it to the application\'s providers.'
        );
    }
}
