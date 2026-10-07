<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect('/login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect('/login');
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:40', 'required_without:code'],
        ]);

        $field = $request->filled('recovery_code') ? 'recovery_code' : 'code';
        $throttleKey = '2fa|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                $field => trans('auth.throttle', [
                    'seconds' => $seconds = RateLimiter::availableIn($throttleKey),
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $valid = $field === 'recovery_code'
            ? $this->twoFactor->consumeRecoveryCode($user, (string) $request->input('recovery_code'))
            : $this->validTotp($user, (string) $request->input('code'));

        if (! $valid) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                $field => $field === 'recovery_code'
                    ? 'That recovery code is invalid or has already been used.'
                    : 'The authentication code is invalid or has expired.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) ($request->session()->get('two_factor.remember', false));
        $request->session()->forget('two_factor');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        $intended = $user->isPatientPortalUser()
            ? route('portal.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($intended);
    }

    private function validTotp(User $user, string $code): bool
    {
        $secret = $this->twoFactor->secretFor($user);

        if ($secret === null || ! $this->twoFactor->verify($secret, $code)) {
            return false;
        }

        // A TOTP code may only be used once (replay protection within its validity window).
        $normalised = preg_replace('/\s+/', '', $code);

        return Cache::add('2fa-used:'.$user->id.':'.$normalised, true, now()->addSeconds(120));
    }

    /**
     * Resolve the half-authenticated user from the session. The lookup goes through the
     * tenant global scope, so a pending login can never be completed on another tenant's host.
     */
    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget('two_factor');

            return null;
        }

        $user = User::find($pending['id'] ?? 0);
        $manager = app(TenantManager::class);

        if ($user === null
            || ! $user->hasTwoFactorEnabled()
            || ($manager->hasCurrent() && $user->tenant_id !== $manager->current()->id)) {
            $request->session()->forget('two_factor');

            return null;
        }

        return $user;
    }
}
