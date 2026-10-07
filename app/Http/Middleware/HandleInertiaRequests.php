<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Share data with every Inertia response.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $manager = app(TenantManager::class);

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'patient_id' => $request->user()->patient_id ?? null,
                ] : null,
            ],

            'tenant' => $manager->hasCurrent() ? [
                'id' => $manager->current()->id,
                'name' => $manager->current()->name,
                'slug' => $manager->current()->slug,
            ] : null,

            'permissions' => $request->user()
                ? $request->user()->getAllPermissions()->pluck('name')
                : [],

            // Super admins have no spatie roles (tenant_id is NULL); expose a virtual
            // 'super-admin' role so the UI can show the platform navigation.
            'roles' => $request->user()
                ? $request->user()->getRoleNames()
                    ->when($request->user()->isSuperAdmin(), fn ($roles) => $roles->push('super-admin'))
                    ->values()
                : [],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
