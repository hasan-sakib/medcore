<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Department;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function __construct(private PatientService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Patient::class);

        $patients = $request->filled('q')
            ? $this->service->search($request->string('q')->toString())
            : Patient::orderBy('created_at', 'desc')->paginate(25);

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'query' => $request->q,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Patients/Create', [
            'departments' => Department::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = $this->service->create($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient registered successfully.');
    }

    public function show(Patient $patient): Response
    {
        $this->authorize('view', $patient);
        $patient->logPhiRead('patient_profile');

        $patient->load([
            'department',
            'encounters' => fn ($q) => $q
                ->with(['clinicalNotes', 'vitals', 'encounterDiagnoses.diagnosis', 'attendingDoctor'])
                ->orderBy('encounter_date', 'desc')
                ->limit(10),
            'appointments' => fn ($q) => $q
                ->with('doctor')
                ->orderBy('scheduled_at', 'desc')
                ->limit(5),
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('update', $patient);
        $patient->logPhiRead('patient_edit_form');

        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
            'departments' => Department::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->service->update($patient, $request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient record updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient record archived.');
    }
}
