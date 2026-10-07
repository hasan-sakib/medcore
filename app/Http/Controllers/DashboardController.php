<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Support\TenantManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|BaseResponse
    {
        $isTenant = app(TenantManager::class)->hasCurrent();

        // A tenant user who lands on the central domain (the session cookie is shared
        // across *.medcore.local) has no tenant context here: send them to their own
        // hospital's subdomain instead of rendering an empty dashboard.
        if (! $isTenant && $request->user()?->tenant_id) {
            $tenant = Tenant::withoutGlobalScopes()->find($request->user()->tenant_id);

            if ($tenant) {
                $baseHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();
                $port = $request->getPort();
                $portPart = in_array($port, [80, 443], true) ? '' : ":{$port}";

                return Inertia::location("{$request->getScheme()}://{$tenant->slug}.{$baseHost}{$portPart}/dashboard");
            }
        }

        $stats = $isTenant ? [
            'patients_today' => Patient::whereDate('created_at', today())->count(),
            'appointments_today' => Appointment::whereDate('scheduled_at', today())
                ->where('status', '!=', 'cancelled')->count(),
            'active_encounters' => Encounter::whereNotNull('admitted_at')
                ->whereNull('discharged_at')->count(),
            'pending_prescriptions' => Prescription::where('status', 'pending')->count(),
        ] : null;

        return Inertia::render('Dashboard', ['stats' => $stats]);
    }
}
