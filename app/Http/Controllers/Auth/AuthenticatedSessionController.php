<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if ($pending = $request->pendingTwoFactorUser()) {
            // Password OK but 2FA outstanding: do not log in, park the user id in the session.
            $request->session()->regenerate();
            $request->session()->put('two_factor', [
                'id' => $pending->id,
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ]);

            return redirect('/two-factor-challenge');
        }

        $request->session()->regenerate();

        $intended = $request->user()->isPatientPortalUser()
            ? route('portal.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($intended);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
