<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function provisionHospital(string $slug): array
{
    $tenant = app(TenantProvisioningService::class)->provision(
        ['name' => ucfirst($slug).' Hospital', 'slug' => $slug, 'plan' => 'professional'],
        ['name' => ucfirst($slug).' Admin', 'email' => "admin@{$slug}.test", 'password' => 'secret-password-1']
    );

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $admin = User::withoutGlobalScopes()->where('email', "admin@{$slug}.test")->firstOrFail();

    return [$tenant, $admin];
}

function isTrashed(int $id): bool
{
    return User::withoutTenant()->withTrashed()->findOrFail($id)->trashed();
}

function staff(Tenant $tenant, string $role): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user = User::factory()->forTenant($tenant)->create()->fresh();
    $user->assignRole($role);

    return $user->fresh();
}

beforeEach(function () {
    [$this->tenant, $admin] = provisionHospital('alpha');
    $this->admin = $admin->fresh();
    [$this->other, $this->otherAdmin] = provisionHospital('beta');
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->base = 'http://alpha.medcore.local';
});

it('lets a tenant admin create a user with a role', function () {
    $this->actingAs($this->admin)
        ->post("{$this->base}/admin/users", [
            'name' => 'Dr Who',
            'email' => 'who@alpha.test',
            'password' => 'a-very-long-password',
            'role' => 'doctor',
        ])
        ->assertRedirect('/admin/users');

    $user = User::withoutGlobalScopes()->where('email', 'who@alpha.test')->firstOrFail();
    expect($user->tenant_id)->toBe($this->tenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    expect($user->fresh()->hasRole('doctor'))->toBeTrue();
});

it('rejects roles that are not assignable or belong to no tenant role set', function () {
    $this->actingAs($this->admin)
        ->post("{$this->base}/admin/users", [
            'name' => 'X', 'email' => 'x@alpha.test', 'password' => 'a-very-long-password', 'role' => 'super-admin',
        ])
        ->assertSessionHasErrors('role');

    expect(User::withoutGlobalScopes()->where('email', 'x@alpha.test')->exists())->toBeFalse();
});

it('assigns the role of the current tenant, never another tenant\'s role', function () {
    $this->actingAs($this->admin)->post("{$this->base}/admin/users", [
        'name' => 'Nurse N', 'email' => 'n@alpha.test', 'password' => 'a-very-long-password', 'role' => 'nurse',
    ]);

    $user = User::withoutGlobalScopes()->where('email', 'n@alpha.test')->firstOrFail();
    $roleTenantIds = DB::table('model_has_roles')->where('model_id', $user->id)->pluck('tenant_id')->all();
    $roleIds = DB::table('model_has_roles')->where('model_id', $user->id)->pluck('role_id')->all();

    expect($roleTenantIds)->toBe([$this->tenant->id]);
    expect(Role::whereIn('id', $roleIds)->pluck('tenant_id')->all())->toBe([$this->tenant->id]);
});

it('only lists users of the current tenant and never super admins', function () {
    staff($this->tenant, 'nurse');
    staff($this->other, 'doctor');
    User::factory()->superAdmin()->create(['email' => 'root@medcore.test']);

    $response = $this->actingAs($this->admin)->get("{$this->base}/admin/users")->assertOk();

    $emails = collect($response->viewData('page')['props']['users']['data'])->pluck('email');
    expect($emails)->toContain('admin@alpha.test')
        ->not->toContain('admin@beta.test')
        ->not->toContain('root@medcore.test');
    expect($emails)->toHaveCount(2);
});

it('returns 404 for another tenant\'s user', function () {
    $foreign = staff($this->other, 'doctor');
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->actingAs($this->admin)->get("{$this->base}/admin/users/{$foreign->id}/edit")->assertNotFound();
    $this->actingAs($this->admin)->patch("{$this->base}/admin/users/{$foreign->id}", [
        'name' => 'Hax', 'email' => 'hax@alpha.test', 'role' => 'doctor', 'is_active' => true,
    ])->assertNotFound();
    $this->actingAs($this->admin)->delete("{$this->base}/admin/users/{$foreign->id}")->assertNotFound();

    expect(isTrashed($foreign->id))->toBeFalse();
});

it('forbids non-admins', function () {
    $nurse = staff($this->tenant, 'nurse');

    $this->actingAs($nurse)->get("{$this->base}/admin/users")->assertForbidden();
    $this->actingAs($nurse)->get("{$this->base}/admin/users/create")->assertForbidden();
    $this->actingAs($nurse)->post("{$this->base}/admin/users", [])->assertForbidden();
    $this->actingAs($nurse)->get("{$this->base}/admin/roles")->assertForbidden();
});

it('updates a user, resets the password and changes the role', function () {
    $doctor = staff($this->tenant, 'doctor');

    $this->actingAs($this->admin)->patch("{$this->base}/admin/users/{$doctor->id}", [
        'name' => 'Renamed', 'email' => $doctor->email, 'role' => 'pharmacist', 'is_active' => true,
        'password' => 'brand-new-password',
    ])->assertRedirect('/admin/users');

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $fresh = $doctor->fresh();
    expect($fresh->name)->toBe('Renamed')
        ->and($fresh->hasRole('pharmacist'))->toBeTrue()
        ->and($fresh->hasRole('doctor'))->toBeFalse()
        ->and(Hash::check('brand-new-password', $fresh->password))->toBeTrue();
});

it('deactivates and restores a user', function () {
    $nurse = staff($this->tenant, 'nurse');

    $this->actingAs($this->admin)->delete("{$this->base}/admin/users/{$nurse->id}")->assertRedirect('/admin/users');
    expect(isTrashed($nurse->id))->toBeTrue();

    $this->actingAs($this->admin)->post("{$this->base}/admin/users/{$nurse->id}/restore")->assertRedirect();
    expect(isTrashed($nurse->id))->toBeFalse();
});

it('cannot delete yourself', function () {
    $second = staff($this->tenant, 'tenant-admin');

    $this->actingAs($second)->delete("{$this->base}/admin/users/{$second->id}")->assertSessionHas('error');
    expect(isTrashed($second->id))->toBeFalse();
});

it('cannot delete or demote the last tenant admin', function () {
    // A non-admin who was granted users.delete directly may not touch admin accounts at all.
    $delegate = staff($this->tenant, 'receptionist');
    $delegate->givePermissionTo(['users.delete', 'users.edit']);
    $this->actingAs($delegate->fresh())->delete("{$this->base}/admin/users/{$this->admin->id}")->assertForbidden();

    // With two admins, one may remove the other ...
    $second = staff($this->tenant, 'tenant-admin');
    $this->actingAs($second)->delete("{$this->base}/admin/users/{$this->admin->id}")->assertRedirect('/admin/users');
    expect(isTrashed($this->admin->id))->toBeTrue();

    // ... but the remaining one is the last admin: cannot be deleted, demoted or deactivated.
    $third = staff($this->tenant, 'tenant-admin');
    $this->actingAs($third)->delete("{$this->base}/admin/users/{$second->id}")->assertRedirect('/admin/users');
    expect(isTrashed($second->id))->toBeTrue();

    // $third is now the only active admin; a second actor (delegate) cannot remove them either.
    $this->actingAs($delegate->fresh())->delete("{$this->base}/admin/users/{$third->id}")->assertForbidden();
    $this->actingAs($third)->delete("{$this->base}/admin/users/{$third->id}")->assertSessionHas('error');
    $this->actingAs($third)->patch("{$this->base}/admin/users/{$third->id}", [
        'name' => $third->name, 'email' => $third->email, 'role' => 'doctor', 'is_active' => true,
    ])->assertSessionHasErrors('role');

    expect(isTrashed($third->id))->toBeFalse();
});

it('cannot deactivate the sole remaining admin through the edit form', function () {
    $actor = staff($this->tenant, 'tenant-admin');
    $this->actingAs($actor)->delete("{$this->base}/admin/users/{$this->admin->id}")->assertRedirect('/admin/users');

    // $actor is the sole admin; deactivating via edit is rejected.
    $this->actingAs($actor)->patch("{$this->base}/admin/users/{$actor->id}", [
        'name' => $actor->name, 'email' => $actor->email, 'role' => 'tenant-admin', 'is_active' => false,
    ])->assertSessionHasErrors('is_active');
    expect(isTrashed($actor->id))->toBeFalse();
});

it('prevents a non-admin from assigning the tenant-admin role', function () {
    $hr = staff($this->tenant, 'receptionist');
    $hr->givePermissionTo('users.create');

    $this->actingAs($hr->fresh())->post("{$this->base}/admin/users", [
        'name' => 'Sneaky', 'email' => 's@alpha.test', 'password' => 'a-very-long-password', 'role' => 'tenant-admin',
    ])->assertSessionHasErrors('role');
});

it('lets the admin edit a role\'s permissions but not the tenant-admin role', function () {
    $nurse = Role::where('tenant_id', $this->tenant->id)->where('name', 'nurse')->firstOrFail();
    $adminRole = Role::where('tenant_id', $this->tenant->id)->where('name', 'tenant-admin')->firstOrFail();
    $before = $adminRole->permissions()->count();

    $this->actingAs($this->admin)->get("{$this->base}/admin/roles")->assertOk();

    $this->actingAs($this->admin)->put("{$this->base}/admin/roles/{$nurse->id}", [
        'permissions' => ['patients.view', 'beds.view'],
    ])->assertRedirect();
    expect($nurse->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['beds.view', 'patients.view']);

    $this->actingAs($this->admin)->put("{$this->base}/admin/roles/{$adminRole->id}", ['permissions' => []])
        ->assertSessionHas('error');
    expect($adminRole->fresh()->permissions()->count())->toBe($before);

    $this->actingAs($this->admin)->put("{$this->base}/admin/roles/{$nurse->id}", ['permissions' => ['nope.nope']])
        ->assertSessionHasErrors('permissions.0');
});

it('cannot edit another tenant\'s role', function () {
    $foreign = Role::where('tenant_id', $this->other->id)->where('name', 'nurse')->firstOrFail();

    $this->actingAs($this->admin)->put("{$this->base}/admin/roles/{$foreign->id}", ['permissions' => []])->assertNotFound();
});
