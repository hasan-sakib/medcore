<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionController extends Controller
{
    public function __construct(private PrescriptionService $prescriptions) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Prescription::class);

        $prescriptions = Prescription::with(['patient', 'prescribedBy', 'items.medicine'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->patient_id, fn ($q, $id) => $q->where('patient_id', $id))
            ->orderBy('prescribed_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'filters' => $request->only('status', 'patient_id'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Prescription::class);

        $medicines = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_type', 'strength']);

        $patient = $request->patient_id
            ? Patient::findOrFail($request->patient_id)
            : null;

        $encounter = $request->encounter_id
            ? Encounter::findOrFail($request->encounter_id)
            : null;

        return Inertia::render('Prescriptions/Create', [
            'medicines' => $medicines,
            'patient' => $patient,
            'encounter' => $encounter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Prescription::class);

        $validated = $request->validate([
            'patient_id' => ['required', TenantRule::exists('patients', 'id')],
            'encounter_id' => ['nullable', TenantRule::exists('encounters', 'id')],
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => ['required', TenantRule::exists('medicines', 'id')],
            'items.*.dosage_instruction' => 'required|string|max:200',
            'items.*.frequency' => 'required|string|max:100',
            'items.*.quantity_prescribed' => 'required|integer|min:1',
            'items.*.duration_days' => 'nullable|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $prescription = $this->prescriptions->create($validated);

        return redirect()->route('prescriptions.show', $prescription);
    }

    public function show(Prescription $prescription): Response
    {
        $this->authorize('view', $prescription);

        $prescription->load(['patient', 'encounter', 'prescribedBy', 'items.medicine', 'dispenseRecords.batch', 'dispenseRecords.dispensedBy']);

        return Inertia::render('Prescriptions/Show', ['prescription' => $prescription]);
    }
}
