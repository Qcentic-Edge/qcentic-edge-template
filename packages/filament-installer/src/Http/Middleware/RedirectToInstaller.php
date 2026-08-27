<?php

namespace QcenticEdge\FilamentInstaller\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use QcenticEdge\FilamentInstaller\Support\InstallerState;
use Symfony\Component\HttpFoundation\Response;

class RedirectToInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        // INSTALLER_ENABLED=false → app is open (stateless retire gate).
        if (InstallerState::isRetired()) {
            return $next($request);
        }

        $path = trim((string) config('installer.path', 'install'), '/');

        if ($request->is($path) || $request->is($path.'/*')) {
            return $next($request);
        }

        // Vite, Livewire (including hashed /livewire-{id}/…), health.
        if ($request->is('_vite*', 'livewire/*', 'livewire-*', 'livewire-*/*', 'up')) {
            return $next($request);
        }

        // Not DB-locked yet, or locked but still waiting for INSTALLER_ENABLED=false:
        // keep traffic on /install (checklist or complete page).
        return redirect()->to(url($path));
    }
}
