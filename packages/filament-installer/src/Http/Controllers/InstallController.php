<?php

namespace QcenticEdge\FilamentInstaller\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use QcenticEdge\FilamentInstaller\Support\InstallerState;
use Throwable;

class InstallController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (InstallerState::isRetired()) {
            return redirect()->to(url('/'));
        }

        if (InstallerState::isInstalled()) {
            return view('installer::complete', [
                'enabled' => (bool) config('installer.enabled', true),
            ]);
        }

        return view('installer::install', [
            'checks' => InstallerState::checks(),
            'ready' => InstallerState::ready(),
            'createUser' => (bool) config('installer.create_user', true),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        if (InstallerState::isRetired() || InstallerState::isInstalled()) {
            return redirect()->to(url('/'));
        }

        if (! InstallerState::ready()) {
            return back()->with('installer_error', 'Environment checks are still failing. Fix them and reload.');
        }

        $user = null;

        if (config('installer.create_user', true)) {
            $user = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => [
                    'required',
                    'string',
                    \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols(),
                ],
            ]);
        }

        try {
            InstallerState::migrate();
            InstallerState::seed();

            if ($user !== null) {
                InstallerState::createUser($user['name'], $user['email'], $user['password']);
            }

            InstallerState::lock();
        } catch (Throwable $e) {
            return back()->with('installer_error', 'Setup failed: '.$e->getMessage());
        }

        return redirect()->route('installer.show');
    }

    /**
     * After DB lock: operator sets INSTALLER_ENABLED=false and redeploys, then
     * hits Check. If the env is off, open the app; otherwise stay on complete.
     */
    public function check(): RedirectResponse
    {
        if (InstallerState::isRetired()) {
            return redirect()->to(url('/'))->with('status', 'Installer retired. App is open.');
        }

        return redirect()
            ->route('installer.show')
            ->with(
                'installer_error',
                'INSTALLER_ENABLED is still true. Set it to false in Magic Containers (or your host env), redeploy, then press Check again.',
            );
    }
}
