<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $enabled = $user->hasTwoFactorEnabled();
        $setup = null;

        // Enrolment started but not yet confirmed: show the secret again.
        if (! $enabled && $user->two_factor_secret !== null) {
            $secret = $this->twoFactor->secretFor($user);
            $url = $this->twoFactor->otpAuthUrl($user, $secret);
            $setup = [
                'secret' => $secret,
                'otpauth_url' => $url,
                'qr' => $this->twoFactor->qrDataUri($url),
            ];
        }

        return Inertia::render('Profile/Security', [
            'twoFactor' => [
                'enabled' => $enabled,
                'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                'recovery_codes_remaining' => $enabled ? $this->twoFactor->recoveryCodesRemaining($user) : 0,
            ],
            'setup' => $setup,
            'recoveryCodes' => session('recovery_codes'),
        ]);
    }

    /** Begin enrolment: generate (and store, encrypted) an unconfirmed secret. */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is already enabled.');
        }

        $this->twoFactor->storeSecret($user, $this->twoFactor->generateSecret());

        return back();
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $secret = $this->twoFactor->secretFor($user);

        if ($user->hasTwoFactorEnabled() || $secret === null) {
            return back()->with('error', 'Start two-factor setup first.');
        }

        if (! $this->twoFactor->verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The authentication code is invalid.']);
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()
            ->with('success', 'Two-factor authentication enabled. Save your recovery codes now.')
            ->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertPassword($request);

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is not enabled.');
        }

        return back()
            ->with('success', 'New recovery codes generated. Previous codes no longer work.')
            ->with('recovery_codes', $this->twoFactor->regenerateRecoveryCodes($user));
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->assertPassword($request);
        $this->twoFactor->disable($request->user());

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    private function assertPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }
    }
}
