<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $patient = $request->user()->patient;

        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->where('status', 'confirmed')
            ->where('scheduled_at', '>=', now())
            ->with('doctor:id,name', 'department:id,name')
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get();

        $recentInvoices = Invoice::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $summary = [
            'total_appointments' => Appointment::where('patient_id', $patient->id)->count(),
            'upcoming_count' => Appointment::where('patient_id', $patient->id)
                ->where('status', 'confirmed')
                ->where('scheduled_at', '>=', now())
                ->count(),
            'unpaid_balance' => Invoice::where('patient_id', $patient->id)
                ->whereIn('status', ['sent', 'partially_paid'])
                ->sum('amount_due'),
        ];

        return Inertia::render('Portal/Dashboard', [
            'patient' => $patient,
            'upcomingAppointments' => $upcomingAppointments,
            'recentInvoices' => $recentInvoices,
            'summary' => $summary,
        ]);
    }
}
