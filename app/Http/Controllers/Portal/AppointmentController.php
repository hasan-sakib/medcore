<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Support\TenantRule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $patient = $request->user()->patient;

        $appointments = Appointment::where('patient_id', $patient->id)
            ->with('doctor:id,name', 'department:id,name')
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return Inertia::render('Portal/Appointments/Index', [
            'appointments' => $appointments,
        ]);
    }

    public function create(Request $request): Response
    {
        $departments = Department::where('is_active', true)->get(['id', 'name']);

        $doctors = $request->department_id
            ? DoctorSchedule::where('department_id', $request->department_id)
                ->where('is_active', true)
                ->with('doctor:id,name')
                ->get()
                ->pluck('doctor')
                ->unique('id')
                ->values()
            : collect();

        return Inertia::render('Portal/Appointments/Book', [
            'departments' => $departments,
            'doctors' => $doctors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', TenantRule::exists('departments', 'id')],
            'doctor_id' => ['required', TenantRule::exists('users', 'id')],
            'scheduled_at' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $patient = $request->user()->patient;

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $data['doctor_id'],
            'department_id' => $data['department_id'],
            'scheduled_at' => $data['scheduled_at'],
            'ends_at' => Carbon::parse($data['scheduled_at'])->addMinutes(30),
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()->route('portal.appointments.index')
            ->with('success', 'Appointment request submitted. You will be notified when confirmed.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_if($appointment->patient_id !== $patient->id, 403);
        abort_if(! in_array($appointment->status, ['pending', 'confirmed']), 422, 'Cannot cancel this appointment.');

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by patient via portal',
        ]);

        return back()->with('success', 'Appointment cancelled.');
    }
}
