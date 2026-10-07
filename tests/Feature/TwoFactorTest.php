<?php

use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Services\TwoFactorService;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = app(TenantProvisioningService::class)->provision(
        ['name' => 'Gamma Hospital', 'slug' => 'gamma', 'plan' => 'professional'],
        ['name' => 'Gamma Admin', 'email' => 'admin@gamma.test', 'password' => 'secret-password-1']
    );
    $this->base = 'http://gamma.medcore.local';

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->user = User::factory()->forTenant($this->tenant)->create(['email' => 'doc@gamma.test'])->fresh();
    $this->user->assignRole('doctor');
    $this->user = $this->user->fresh();
});

/** Enrol the user through the real endpoints; returns [secret, recoveryCodes]. */
function enrol(object $test): array
{
    $test->actingAs($test->user)->post("{$test->base}/profile/security/two-factor")->assertRedirect();

    $secret = app(TwoFactorService::class)->secretFor($test->user->fresh());
    $code = (new Google2FA)->getCurrentOtp($secret);

    $response = $test->actingAs($test->user)
        ->from("{$test->base}/profile/security")
        ->post("{$test->base}/profile/security/two-factor/confirm", ['code' => $code]);
    $response->assertRedirect()->assertSessionHas('recovery_codes');

    $codes = session('recovery_codes');
    auth()->guard('web')->logout();
    test()->flushSession();

    return [$secret, $codes];
}

it('logs in without a challenge when 2FA is not enabled', function () {
    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password'])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($this->user);
});

it('enrols, stores secrets encrypted and shows the security page', function () {
    $this->actingAs($this->user)->get("{$this->base}/profile/security")->assertOk();

    [$secret, $codes] = enrol($this);

    $fresh = User::withoutGlobalScopes()->find($this->user->id);
    expect($fresh->hasTwoFactorEnabled())->toBeTrue()
        ->and($fresh->two_factor_secret)->not->toBe($secret)
        ->and($codes)->toHaveCount(8)
        ->and($fresh->two_factor_recovery_codes)->not->toContain($codes[0]);
});

it('does not confirm enrolment with a wrong code', function () {
    $this->actingAs($this->user)->post("{$this->base}/profile/security/two-factor");
    $this->actingAs($this->user)->post("{$this->base}/profile/security/two-factor/confirm", ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('requires a TOTP challenge after the password when 2FA is enabled', function () {
    [$secret] = enrol($this);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password'])
        ->assertRedirect('/two-factor-challenge');
    $this->assertGuest();

    // Protected pages are still unreachable while half-authenticated.
    $this->get("{$this->base}/dashboard")->assertRedirect();
    $this->assertGuest();

    $this->get("{$this->base}/two-factor-challenge")->assertOk();

    $this->post("{$this->base}/two-factor-challenge", ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($this->user);
});

it('rejects an invalid TOTP code and stays logged out', function () {
    enrol($this);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $this->post("{$this->base}/two-factor-challenge", ['code' => '123456'])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('does not allow a TOTP code to be replayed', function () {
    [$secret] = enrol($this);
    $code = (new Google2FA)->getCurrentOtp($secret);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $this->post("{$this->base}/two-factor-challenge", ['code' => $code])->assertRedirect('/dashboard');

    auth()->guard('web')->logout();
    $this->flushSession();

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $this->post("{$this->base}/two-factor-challenge", ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('accepts each recovery code exactly once', function () {
    [, $codes] = enrol($this);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $this->post("{$this->base}/two-factor-challenge", ['recovery_code' => $codes[0]])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($this->user);

    auth()->guard('web')->logout();
    $this->flushSession();

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $this->post("{$this->base}/two-factor-challenge", ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();

    // A different code still works.
    $this->post("{$this->base}/two-factor-challenge", ['recovery_code' => strtoupper($codes[1])])->assertRedirect('/dashboard');
    expect(app(TwoFactorService::class)->recoveryCodesRemaining($this->user->fresh()))->toBe(6);
});

it('throttles repeated bad codes', function () {
    [$secret] = enrol($this);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    foreach (range(1, 5) as $_) {
        $this->post("{$this->base}/two-factor-challenge", ['code' => '111111'])->assertSessionHasErrors('code');
    }

    // Even the right code is refused while locked out.
    $this->post("{$this->base}/two-factor-challenge", ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('redirects the challenge page to login when there is no pending login', function () {
    $this->get("{$this->base}/two-factor-challenge")->assertRedirect('/login');
    $this->post("{$this->base}/two-factor-challenge", ['code' => '123456'])->assertRedirect('/login');
});

it('cannot complete a pending login on another tenant\'s host', function () {
    [$secret] = enrol($this);

    app(TenantProvisioningService::class)->provision(
        ['name' => 'Delta Hospital', 'slug' => 'delta', 'plan' => 'professional'],
        ['name' => 'Delta Admin', 'email' => 'admin@delta.test', 'password' => 'secret-password-1']
    );

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'password']);
    $pending = session('two_factor');

    // Carry the half-authenticated session to the other tenant's host.
    $this->flushSession();
    $this->withSession(['two_factor' => $pending]);

    $this->post('http://delta.medcore.local/two-factor-challenge', ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertRedirect('/login');
    $this->assertGuest();
});

it('does not tell a wrong-password attempt apart from the 2FA flow', function () {
    enrol($this);

    $this->post("{$this->base}/login", ['email' => 'doc@gamma.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    expect(session('two_factor'))->toBeNull();
});

it('disables 2FA only with the correct password', function () {
    enrol($this);

    $this->actingAs($this->user)->delete("{$this->base}/profile/security/two-factor", ['password' => 'nope'])
        ->assertSessionHasErrors('password');
    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->actingAs($this->user)->delete("{$this->base}/profile/security/two-factor", ['password' => 'password'])
        ->assertRedirect();
    $fresh = $this->user->fresh();
    expect($fresh->hasTwoFactorEnabled())->toBeFalse()
        ->and($fresh->two_factor_secret)->toBeNull()
        ->and($fresh->two_factor_recovery_codes)->toBeNull();
});

it('regenerates recovery codes, invalidating the old ones', function () {
    [, $old] = enrol($this);

    $this->actingAs($this->user)
        ->post("{$this->base}/profile/security/two-factor/recovery-codes", ['password' => 'password'])
        ->assertSessionHas('recovery_codes');
    $new = session('recovery_codes');

    $service = app(TwoFactorService::class);
    expect($new)->toHaveCount(8)
        ->and($service->consumeRecoveryCode($this->user->fresh(), $old[0]))->toBeFalse()
        ->and($service->consumeRecoveryCode($this->user->fresh(), $new[0]))->toBeTrue();
});
