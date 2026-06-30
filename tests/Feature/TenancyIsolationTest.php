<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

// RefreshDatabase (declared in Pest.php) runs all migrations automatically.
// Spatie permission migrations must be published before running tests: make install

it('auto-stamps tenant_id on model creation', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user->tenant_id)->toBe($tenant->id);
});

it('tenant A cannot read tenant B users', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Create a user in Tenant B context
    app(TenantManager::class)->setCurrent($tenantB);
    User::create([
        'name' => 'Tenant B User',
        'email' => 'b@example.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenantB->id,
    ]);

    // Switch to Tenant A — should see zero users
    app(TenantManager::class)->setCurrent($tenantA);
    $users = User::all();

    expect($users)->toHaveCount(0);
});

it('tenant A cannot write records with tenant B id', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(TenantManager::class)->setCurrent($tenantA);

    // Even if caller explicitly passes tenant_id = B, it should be overridden by BelongsToTenant boot
    $user = User::create([
        'name' => 'Hijack Attempt',
        'email' => 'hijack@example.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenantB->id, // attempted cross-tenant write
    ]);

    // The trait's creating() should have re-stamped to tenant A
    expect($user->tenant_id)->toBe($tenantA->id);
});

it('withoutTenant bypasses the scope', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(TenantManager::class)->setCurrent($tenantB);
    User::create([
        'name' => 'B User',
        'email' => 'b2@example.com',
        'password' => bcrypt('password'),
        'tenant_id' => $tenantB->id,
    ]);

    app(TenantManager::class)->setCurrent($tenantA);

    // withoutTenant() should see all users across tenants
    $allUsers = User::withoutTenant()->get();
    expect($allUsers)->toHaveCount(1);
});

it('provisions tenant with roles and admin user via TenantProvisioningService', function () {
    $service = app(TenantProvisioningService::class);

    $tenant = $service->provision(
        tenantData: ['name' => 'Test Hospital', 'slug' => 'testhospital', 'plan' => 'trial'],
        adminData: ['name' => 'Admin', 'email' => 'admin@testhospital.test', 'password' => 'securepass123!'],
    );

    expect($tenant->slug)->toBe('testhospital');
    expect($tenant->status)->toBe('active');

    app(TenantManager::class)->setCurrent($tenant);

    $admin = User::where('email', 'admin@testhospital.test')->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('tenant-admin'))->toBeTrue();
});

it('suspended tenant returns 403 on web request', function () {
    $tenant = Tenant::factory()->create(['status' => 'suspended', 'slug' => 'suspended-hosp']);

    $response = $this->get('http://suspended-hosp.medcore.local/login');

    $response->assertStatus(403);
});

it('unknown tenant subdomain returns 404', function () {
    $response = $this->get('http://unknown-xyz.medcore.local/login');

    $response->assertStatus(404);
});
