<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\BedAllocationService;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BedAllocationController extends Controller
{
    public function __construct(private BedAllocationService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BedAllocation::class);

        $data = $request->validate([
            'bed_id' => ['required', TenantRule::exists('beds', 'id')],
            'patient_id' => ['required', TenantRule::exists('patients', 'id')],
            'encounter_id' => ['nullable', TenantRule::exists('encounters', 'id')],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $bed = Bed::findOrFail($data['bed_id']);
        $patient = Patient::findOrFail($data['patient_id']);
        $encounter = isset($data['encounter_id']) ? Encounter::findOrFail($data['encounter_id']) : null;

        try {
            $this->service->admit($bed, $patient, $encounter, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['bed_id' => $e->getMessage()]);
        }

        return back()->with('success', "Patient admitted to bed {$bed->bed_number}.");
    }

    public function discharge(Request $request, BedAllocation $bedAllocation): RedirectResponse
    {
        $this->authorize('update', $bedAllocation);

        $data = $request->validate([
            'discharge_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->service->discharge($bedAllocation, $data['discharge_reason'], $request->user());

        return back()->with('success', 'Patient discharged successfully.');
    }
}
