<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = $this->tenantId($request);

        $counts = DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('users.tenant_id', $tenantId)
            ->whereNull('users.deleted_at')
            ->groupBy('model_has_roles.role_id')
            ->selectRaw('model_has_roles.role_id as role_id, count(*) as total')
            ->pluck('total', 'role_id');

        $roles = Role::where('tenant_id', $tenantId)
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'locked' => $r->name === 'tenant-admin',
                'users_count' => (int) ($counts[$r->id] ?? 0),
                'permissions' => $r->permissions->pluck('name')->sort()->values(),
            ])
            ->values();

        $modules = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $name) => explode('.', $name)[0])
            ->map(fn ($names, $module) => ['module' => $module, 'permissions' => $names->values()])
            ->values();

        return Inertia::render('Roles/Index', [
            'tenantRoles' => $roles,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tenantId = $this->tenantId($request);

        $role = Role::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        if ($role->name === 'tenant-admin') {
            return back()->with('error', 'The tenant admin role always has every permission and cannot be edited.');
        }

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', $validated['permissions'])->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Permissions for {$role->name} updated.");
    }

    private function tenantId(Request $request): int
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if($tenantId === null, 403, 'Tenant context required.');

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        return $tenantId;
    }
}
