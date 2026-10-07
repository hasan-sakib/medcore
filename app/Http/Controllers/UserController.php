<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tenant user management. Every query is explicitly constrained to the acting
 * user's tenant (in addition to the global TenantScope), and spatie's team
 * context is pinned to that tenant before any role assignment.
 */
class UserController extends Controller
{
    /** Roles a tenant admin may hand out. */
    public const ASSIGNABLE_ROLES = ['tenant-admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'cashier'];

    public function index(Request $request): Response
    {
        $tenantId = $this->tenantId($request);
        $search = trim((string) $request->query('search', ''));

        $users = $this->staffQuery($tenantId)
            ->with('roles')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $u) => $this->present($u, $request->user()));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->tenantId($request);

        return Inertia::render('Users/Create', ['assignableRoles' => $this->assignableRoleNames($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'role' => ['required', 'string', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $role = $this->tenantRole($tenantId, $validated['role']);
        $this->guardAdminRoleGrant($request, $role->name);

        DB::transaction(function () use ($validated, $tenantId, $role) {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'tenant_id' => $tenantId,
                'email_verified_at' => now(),
            ])->save();

            $this->pinTeam($tenantId);
            $user->assignRole($role);
        });

        return redirect('/admin/users')->with('success', 'User created.');
    }

    public function edit(Request $request, int $id): Response
    {
        $tenantId = $this->tenantId($request);
        $user = $this->findUser($tenantId, $id);

        return Inertia::render('Users/Edit', [
            'user' => $this->present($user->load('roles'), $request->user()),
            'assignableRoles' => $this->assignableRoleNames($request),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $user = $this->findUser($tenantId, $id);
        $actor = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(self::ASSIGNABLE_ROLES)],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:12', 'max:255'],
        ]);

        $role = $this->tenantRole($tenantId, $validated['role']);
        $this->guardAdminRoleGrant($request, $role->name);
        $this->guardAdminTarget($request, $user);

        $demoting = $role->name !== 'tenant-admin' && $this->isAdmin($user);
        $deactivating = ! $validated['is_active'] && ! $user->trashed();

        if ($deactivating && $user->is($actor)) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        if (($demoting || $deactivating) && $this->isLastTenantAdmin($tenantId, $user)) {
            throw ValidationException::withMessages([
                $demoting ? 'role' : 'is_active' => 'The last active tenant admin cannot be demoted or deactivated.',
            ]);
        }

        DB::transaction(function () use ($user, $validated, $role, $tenantId, $deactivating) {
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();

            $this->pinTeam($tenantId);
            $user->syncRoles([$role]);

            if ($deactivating) {
                $user->delete();
            } elseif ($validated['is_active'] && $user->trashed()) {
                $user->restore();
            }
        });

        return redirect('/admin/users')->with('success', 'User updated.');
    }

    /** Deactivate (soft delete). The row and its history are kept; the user can no longer sign in. */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $user = $this->findUser($tenantId, $id);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $this->guardAdminTarget($request, $user);

        if ($this->isLastTenantAdmin($tenantId, $user)) {
            return back()->with('error', 'The last tenant admin cannot be deleted.');
        }

        $user->delete();

        return redirect('/admin/users')->with('success', 'User deactivated.');
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $user = $this->findUser($tenantId, $id);

        $this->guardAdminTarget($request, $user);
        $user->restore();

        return back()->with('success', 'User reactivated.');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** The acting user's tenant id; also pins spatie's team context. */
    private function tenantId(Request $request): int
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $this->pinTeam($tenantId);

        return $tenantId;
    }

    private function pinTeam(int $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
    }

    /** Staff accounts of one tenant, including deactivated ones. Never super-admins or portal accounts. */
    private function staffQuery(int $tenantId)
    {
        return User::withTrashed()
            ->where('users.tenant_id', $tenantId)
            ->whereNull('users.patient_id');
    }

    private function findUser(int $tenantId, int $id): User
    {
        return $this->staffQuery($tenantId)->where('users.id', $id)->firstOrFail();
    }

    private function tenantRole(int $tenantId, string $name): Role
    {
        return Role::where('tenant_id', $tenantId)->where('guard_name', 'web')->where('name', $name)->firstOrFail();
    }

    /** @return list<string> */
    private function assignableRoleNames(Request $request): array
    {
        $names = Role::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('name', self::ASSIGNABLE_ROLES)
            ->pluck('name')
            ->all();

        // Only tenant admins can hand out the tenant-admin role.
        return array_values(array_filter(
            $names,
            fn (string $n) => $n !== 'tenant-admin' || $request->user()->hasRole('tenant-admin'),
        ));
    }

    private function isAdmin(User $user): bool
    {
        return $user->loadMissing('roles')->roles->contains('name', 'tenant-admin');
    }

    /** Privilege-escalation guard: only tenant admins may grant the tenant-admin role. */
    private function guardAdminRoleGrant(Request $request, string $roleName): void
    {
        if ($roleName === 'tenant-admin' && ! $request->user()->hasRole('tenant-admin')) {
            throw ValidationException::withMessages(['role' => 'Only a tenant admin can assign the tenant admin role.']);
        }
    }

    /** Non-admins may not modify tenant-admin accounts. */
    private function guardAdminTarget(Request $request, User $target): void
    {
        abort_if(
            $this->isAdmin($target) && ! $request->user()->hasRole('tenant-admin'),
            403,
            'Only a tenant admin can modify a tenant admin account.',
        );
    }

    /** True when $user is an active tenant admin and no other active tenant admin exists. */
    private function isLastTenantAdmin(int $tenantId, User $user): bool
    {
        if ($user->trashed() || ! $this->isAdmin($user)) {
            return false;
        }

        $adminRole = $this->tenantRole($tenantId, 'tenant-admin');

        $otherAdmins = DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.role_id', $adminRole->id)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('users.tenant_id', $tenantId)
            ->whereNull('users.deleted_at')
            ->where('users.id', '!=', $user->id)
            ->count();

        return $otherAdmins === 0;
    }

    /** @return array<string, mixed> */
    private function present(User $u, User $actor): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->roles->pluck('name')->values(),
            'role' => $u->roles->first()?->name,
            'is_active' => ! $u->trashed(),
            'two_factor_enabled' => $u->hasTwoFactorEnabled(),
            'is_self' => $u->is($actor),
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
