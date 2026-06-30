<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantProvisioningService
{
    /**
     * All roles and their default permission slugs for a new tenant.
     */
    private const ROLE_PERMISSIONS = [
        'tenant-admin' => ['*'],  // wildcard — all current + future permissions
        'doctor' => [
            'patients.view', 'patients.edit',
            'encounters.view', 'encounters.create', 'encounters.edit',
            'clinical-notes.create', 'clinical-notes.edit',
            'prescriptions.create', 'appointments.view', 'appointments.edit',
        ],
        'nurse' => [
            'patients.view', 'encounters.view', 'vitals.create', 'vitals.edit',
            'beds.view', 'bed-allocations.create',
        ],
        'receptionist' => [
            'patients.view', 'patients.create',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'invoices.view', 'invoices.create',
        ],
        'pharmacist' => [
            'medicines.view', 'medicines.edit',
            'medicine-batches.view', 'medicine-batches.create',
            'dispense-records.create',
            'prescriptions.view',
        ],
        'cashier' => [
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.create', 'claims.view', 'claims.create',
        ],
    ];

    /** All granular permissions registered in the system. */
    private const ALL_PERMISSIONS = [
        'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
        'encounters.view', 'encounters.create', 'encounters.edit',
        'clinical-notes.create', 'clinical-notes.edit',
        'vitals.create', 'vitals.edit',
        'diagnoses.view', 'diagnoses.create',
        'prescriptions.view', 'prescriptions.create',
        'appointments.view', 'appointments.create', 'appointments.edit',
        'medicines.view', 'medicines.edit',
        'medicine-batches.view', 'medicine-batches.create',
        'dispense-records.create',
        'beds.view', 'bed-allocations.create', 'bed-allocations.edit',
        'wards.view',
        'invoices.view', 'invoices.create', 'invoices.edit',
        'payments.create',
        'claims.view', 'claims.create',
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'roles.manage',
        'reports.view',
    ];

    public function provision(array $tenantData, array $adminData): Tenant
    {
        return DB::transaction(function () use ($tenantData, $adminData): Tenant {
            $tenant = Tenant::create([
                'name'   => $tenantData['name'],
                'slug'   => $tenantData['slug'] ?? Str::slug($tenantData['name']),
                'domain' => $tenantData['domain'] ?? null,
                'status' => 'active',
                'subscription_plan' => $tenantData['plan'] ?? 'trial',
                'trial_ends_at' => now()->addDays(30),
            ]);

            $this->seedPermissions();
            $this->seedRoles($tenant);

            $this->createTenantAdmin($tenant, $adminData);

            return $tenant->fresh();
        });
    }

    private function seedPermissions(): void
    {
        // In spatie teams mode, permissions are GLOBAL (no tenant_id column on permissions table).
        // Tenant scoping happens at the role level and the model_has_roles pivot.
        foreach (self::ALL_PERMISSIONS as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }

    private function seedRoles(Tenant $tenant): void
    {
        // Roles ARE tenant-scoped (tenant_id on roles table via teams mode).
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach (self::ROLE_PERMISSIONS as $roleName => $perms) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web', 'tenant_id' => $tenant->id]
            );

            $permissions = $perms === ['*']
                ? Permission::whereIn('name', self::ALL_PERMISSIONS)->get()
                : Permission::whereIn('name', $perms)->get();

            $role->syncPermissions($permissions);
        }
    }

    private function createTenantAdmin(Tenant $tenant, array $adminData): User
    {
        $user = User::create([
            'name'      => $adminData['name'],
            'email'     => $adminData['email'],
            'password'  => bcrypt($adminData['password']),
            'tenant_id' => $tenant->id,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('tenant-admin');

        return $user;
    }
}
