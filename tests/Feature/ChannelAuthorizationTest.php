<?php

use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Broadcast;

/**
 * Invoke the callback registered for a channel pattern directly, the same way
 * the broadcaster does after it has matched the channel name.
 */
function authorizeChannel(User $user, string $channelName): mixed
{
    $broadcaster = Broadcast::driver();
    $channels = (new ReflectionClass(Broadcaster::class))->getProperty('channels');
    $registered = $channels->getValue($broadcaster);

    foreach ($registered as $pattern => $callback) {
        $regex = '/^'.preg_replace('/\{[^.}]+\}/', '([^.]+)', str_replace('.', '\.', $pattern)).'$/';
        if (preg_match($regex, $channelName, $m)) {
            array_shift($m);
            $params = array_map(fn ($v) => ctype_digit($v) ? (int) $v : $v, $m);

            return $callback($user, ...$params);
        }
    }

    throw new RuntimeException("No channel registered for [$channelName].");
}

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenantA = $provisioner->provision(
        ['name' => 'Chan A', 'slug' => 'chana', 'plan' => 'professional'],
        ['name' => 'A Admin', 'email' => 'admin@chana.test', 'password' => 'pass']
    );
    $this->tenantB = $provisioner->provision(
        ['name' => 'Chan B', 'slug' => 'chanb', 'plan' => 'professional'],
        ['name' => 'B Admin', 'email' => 'admin@chanb.test', 'password' => 'pass']
    );

    $make = function ($tenant, string $role) {
        app(TenantManager::class)->setCurrent($tenant);
        $user = User::factory()->forTenant($tenant)->create();
        $user->assignRole($role);

        return $user;
    };

    $this->nurseA = $make($this->tenantA, 'nurse');          // beds.view + operating-rooms.view
    $this->pharmacistA = $make($this->tenantA, 'pharmacist'); // neither permission
    $this->nurseB = $make($this->tenantB, 'nurse');

    app(TenantManager::class)->setCurrent($this->tenantA);

    // The test env uses the 'log' broadcaster, which approves every channel.
    // Switch to the real Reverb broadcaster (auth only needs key/secret, no
    // network) and re-register the channel callbacks on it.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);
    Broadcast::purge();
    require base_path('routes/channels.php');
});

it('allows a user with the right tenant and permission on the beds channel', function () {
    expect(authorizeChannel($this->nurseA, "tenant.{$this->tenantA->id}.beds"))->toBeTrue();
});

it('denies a user from tenant A on the tenant B beds channel', function () {
    expect(authorizeChannel($this->nurseA, "tenant.{$this->tenantB->id}.beds"))->toBeFalse();
});

it('denies a user of the right tenant who lacks beds.view', function () {
    expect(authorizeChannel($this->pharmacistA, "tenant.{$this->tenantA->id}.beds"))->toBeFalse();
});

it('applies the same rules to the operating-room channel', function () {
    expect(authorizeChannel($this->nurseA, "tenant.{$this->tenantA->id}.or"))->toBeTrue();
    expect(authorizeChannel($this->nurseA, "tenant.{$this->tenantB->id}.or"))->toBeFalse();
    expect(authorizeChannel($this->pharmacistA, "tenant.{$this->tenantA->id}.or"))->toBeFalse();
});

function channelAuth($test, string $channel)
{
    return $test->post('http://chana.medcore.local/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-'.$channel,
    ]);
}

it('/broadcasting/auth signs the subscription for the right tenant and permission', function () {
    $this->actingAs($this->nurseA);

    channelAuth($this, "tenant.{$this->tenantA->id}.beds")
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

it('/broadcasting/auth refuses a cross-tenant subscription', function () {
    $this->actingAs($this->nurseA);

    channelAuth($this, "tenant.{$this->tenantB->id}.beds")->assertForbidden();
    channelAuth($this, "tenant.{$this->tenantB->id}.or")->assertForbidden();
});

it('/broadcasting/auth refuses a user without the permission', function () {
    $this->actingAs($this->pharmacistA);

    channelAuth($this, "tenant.{$this->tenantA->id}.beds")->assertForbidden();
});

it('/broadcasting/auth refuses unauthenticated requests', function () {
    channelAuth($this, "tenant.{$this->tenantA->id}.beds")->assertForbidden();
});
