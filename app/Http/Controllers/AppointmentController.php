<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::with(['patient', 'doctor', 'department'])
            ->when($request->date, fn ($q) => $q->whereDate('scheduled_at', $request->date))
            ->when($request->doctor_id, fn ($q) => $q->where('doctor_id', $request->doctor_id))
            ->orderBy('scheduled_at')
            ->paginate(25);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'doctors' => User::role('doctor')->get(['id', 'name']),
            'filters' => $request->only(['date', 'doctor_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('Appointments/Create', [
            'doctors' => User::role('doctor')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->get(['id', 'name']),
            'patient' => $request->patient_id
                ? Patient::findOrFail($request->patient_id)
                : null,
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        try {
            $appointment = $this->service->book($request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scheduled_at' => $e->getMessage()])->withInput();
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment booked.');
    }

    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor', 'department', 'encounter']);

        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment->load(['patient', 'doctor', 'department']),
            'doctors' => User::role('doctor')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,checked_in,completed,cancelled,no_show'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment->update($validated);

        return back()->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        $this->service->cancel($appointment, auth()->id(), 'Deleted by staff');

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment cancelled.');
    }

    /** GET /appointments/slots?doctor_id=&date= */
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $slots = $this->service->availableSlots(
            (int) $request->doctor_id,
            Carbon::parse($request->date)
        );

        return response()->json([
            'slots' => array_map(fn ($s) => [
                'start' => $s['start']->toIso8601String(),
                'end' => $s['end']->toIso8601String(),
            ], $slots),
        ]);
    }
}
