<?php

namespace Mamenein\FilamentInstaller\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mamenein\FilamentInstaller\Support\InstallerState;
use Symfony\Component\HttpFoundation\Response;

class RedirectToInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallerState::isInstalled()) {
            return $next($request);
        }

        $path = trim((string) config('installer.path', 'install'), '/');

        if ($request->is($path) || $request->is($path.'/*')) {
            return $next($request);
        }

        // Let framework internals (Vite dev assets, Livewire messages, the
        // health check) through so the installer page itself still works.
        if ($request->is('_vite*', 'livewire/*', 'up')) {
            return $next($request);
        }

        return redirect()->to(url($path));
    }
}
