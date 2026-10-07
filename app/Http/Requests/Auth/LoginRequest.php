<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** Set when the password was correct but a 2FA challenge is still outstanding. */
    private ?User $pendingTwoFactorUser = null;

    public function pendingTwoFactorUser(): ?User
    {
        return $this->pendingTwoFactorUser;
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Users with confirmed 2FA are NOT logged in here: validate the password only
        // (the provider query is tenant-scoped, exactly like Auth::attempt) and let the
        // two-factor challenge complete the login.
        $provider = Auth::guard('web')->getProvider();
        $credentials = $this->only('email', 'password');
        $candidate = $provider->retrieveByCredentials($credentials);

        if ($candidate instanceof User
            && $candidate->hasTwoFactorEnabled()
            && $provider->validateCredentials($candidate, $credentials)) {
            RateLimiter::clear($this->throttleKey());
            $this->pendingTwoFactorUser = $candidate;

            return;
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
