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

            'roles' => $request->user()
                ? $request->user()->getRoleNames()
                : [],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
