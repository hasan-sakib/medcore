<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Encounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EncounterController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Encounter::class);

        $encounters = Encounter::with(['patient', 'attendingDoctor', 'department'])
            ->when($request->date, fn ($q) => $q->whereDate('encounter_date', $request->date))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('encounter_date', 'desc')
            ->paginate(25);

        return Inertia::render('Encounters/Index', [
            'encounters' => $encounters,
            'filters' => $request->only(['date', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Encounter::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'attending_doctor_id' => ['required', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'encounter_type' => ['required', 'in:outpatient,inpatient,emergency,teleconsult'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'encounter_date' => ['required', 'date'],
        ]);

        $encounter = Encounter::create($validated + ['status' => 'open']);

        // Update linked appointment to checked_in
        if (! empty($validated['appointment_id'])) {
            Appointment::where('id', $validated['appointment_id'])
                ->update(['status' => 'checked_in']);
        }

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Encounter opened.');
    }

    public function show(Encounter $encounter): Response
    {
        $this->authorize('view', $encounter);
        $encounter->patient->logPhiRead('encounter_view');

        $encounter->load([
            'patient',
            'attendingDoctor',
            'department',
            'appointment',
            'clinicalNotes.author',
            'vitals.recordedBy',
            'encounterDiagnoses.diagnosis',
            'encounterDiagnoses.createdBy',
        ]);

        return Inertia::render('Encounters/Show', [
            'encounter' => $encounter,
        ]);
    }

    public function update(Request $request, Encounter $encounter): RedirectResponse
    {
        $this->authorize('update', $encounter);

        $validated = $request->validate([
            'status' => ['nullable', 'in:open,in_progress,completed,cancelled'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'discharged_at' => ['nullable', 'date'],
        ]);

        $encounter->update($validated);

        // Sync linked appointment when encounter is completed
        if (($validated['status'] ?? null) === 'completed' && $encounter->appointment_id) {
            Appointment::where('id', $encounter->appointment_id)
                ->update(['status' => 'completed']);
        }

        return back()->with('success', 'Encounter updated.');
    }
}
