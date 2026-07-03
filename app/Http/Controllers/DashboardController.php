<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\TenantManager;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $isTenant = app(TenantManager::class)->hasCurrent();

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
