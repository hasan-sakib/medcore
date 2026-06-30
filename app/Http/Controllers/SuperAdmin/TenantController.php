<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(private TenantProvisioningService $provisioner) {}

    public function index(): Response
    {
        $tenants = Tenant::withoutGlobalScopes()
            ->withTrashed()
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SuperAdmin/Tenants/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['required', 'string', 'max:63', 'alpha_dash', 'unique:tenants,slug'],
            'plan'           => ['nullable', 'string', 'in:trial,basic,professional,enterprise'],
            'admin_name'     => ['required', 'string', 'max:255'],
            'admin_email'    => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:12'],
        ]);

        $tenant = $this->provisioner->provision(
            tenantData: [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'plan' => $validated['plan'] ?? 'trial',
            ],
            adminData: [
                'name'     => $validated['admin_name'],
                'email'    => $validated['admin_email'],
                'password' => $validated['admin_password'],
            ]
        );

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" provisioned successfully.");
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['users' => fn ($q) => $q->limit(50)]);

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended'],
            'name'   => ['required', 'string', 'max:255'],
            'plan'   => ['nullable', 'string', 'in:trial,basic,professional,enterprise'],
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete(); // soft delete

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" suspended.");
    }
}
