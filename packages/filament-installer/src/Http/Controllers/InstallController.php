<?php

namespace Mamenein\FilamentInstaller\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Mamenein\FilamentInstaller\Support\InstallerState;
use Throwable;

class InstallController extends Controller
{
    public function show(): View
    {
        abort_if(InstallerState::isInstalled(), 404);

        return view('installer::install', [
            'checks' => InstallerState::checks(),
            'ready' => InstallerState::ready(),
            'createUser' => (bool) config('installer.create_user', true),
        ]);
    }

    public function run(\Illuminate\Http\Request $request): RedirectResponse
    {
        abort_if(InstallerState::isInstalled(), 404);

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
        } catch (Throwable $e) {
            return back()->with('installer_error', 'Setup failed: '.$e->getMessage());
        }

        InstallerState::lock();

        return redirect()->to(url('/'))->with('status', 'Installation complete.');
    }
}
