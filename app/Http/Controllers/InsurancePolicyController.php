<?php

namespace App\Http\Controllers;

use App\Models\InsurancePolicy;
use App\Models\Patient;
use App\Services\PatientService;
use App\Support\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InsurancePolicyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'patient_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $patient = isset($filters['patient_id'])
            ? Patient::findOrFail($filters['patient_id'])
            : null;

        $policies = InsurancePolicy::with('patient:id,mrn,first_name,last_name')
            ->when($patient, fn ($q) => $q->where('patient_id', $patient->id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $like = '%'.addcslashes($s, '%_\\').'%';
                $q->where(function ($w) use ($like) {
                    $w->where('provider_name', 'like', $like)
                        ->orWhere('policy_number', 'like', $like)
                        ->orWhereHas('patient', fn ($p) => $p->where('mrn', 'like', $like));
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('valid_from')
            ->paginate(20)
            ->withQueryString();

        $policies->getCollection()->transform(
            fn (InsurancePolicy $p) => $this->present($p) + ['is_current' => $p->isCurrentlyActive()]
        );

        return Inertia::render('Billing/InsurancePolicies/Index', [
            'policies' => $policies,
            'patient' => $patient?->only(['id', 'mrn', 'first_name', 'last_name']),
            'filters' => (object) array_filter(
                collect($filters)->except('patient_id')->all(),
                fn ($v) => $v !== null && $v !== '',
            ),
        ]);
    }

    /** JSON patient lookup for the create form (MRN fragment, or name/phone/ID via blind index). */
    public function patientSearch(Request $request, PatientService $patients): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $found = Patient::where('mrn', 'like', '%'.addcslashes(strtoupper($term), '%_\\').'%')
            ->limit(10)->get();

        if ($found->isEmpty()) {
            $found = $patients->search($term, 10)->getCollection();
        }

        return response()->json(
            $found->map(fn (Patient $p) => $p->only(['id', 'mrn', 'first_name', 'last_name']))->values()
        );
    }

    public function create(Request $request): Response
    {
        $patient = $request->filled('patient_id')
            ? Patient::findOrFail($request->integer('patient_id'))
            : null;

        return Inertia::render('Billing/InsurancePolicies/Create', [
            'patient' => $patient?->only(['id', 'mrn', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = app(TenantManager::class)->current()->id;

        $data = $request->validate([
            'patient_id' => [
                'required', 'integer',
                Rule::exists('patients', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            ...$this->rules($tenantId),
        ]);

        $policy = InsurancePolicy::create($this->normalise($data));

        return redirect('/billing/insurance-policies?patient_id='.$policy->patient_id)
            ->with('success', 'Insurance policy added.');
    }

    public function edit(InsurancePolicy $insurancePolicy): Response
    {
        $insurancePolicy->load('patient:id,mrn,first_name,last_name');

        return Inertia::render('Billing/InsurancePolicies/Edit', [
            'policy' => $this->present($insurancePolicy),
        ]);
    }

    public function update(Request $request, InsurancePolicy $insurancePolicy): RedirectResponse
    {
        $tenantId = app(TenantManager::class)->current()->id;

        // The owning patient is fixed once a policy exists (claims reference it).
        $data = $request->validate($this->rules($tenantId, $insurancePolicy));

        $insurancePolicy->update($this->normalise($data));

        return redirect('/billing/insurance-policies?patient_id='.$insurancePolicy->patient_id)
            ->with('success', 'Insurance policy updated.');
    }

    /** Remove a policy with no claims; deactivate one that claims already reference. */
    public function destroy(InsurancePolicy $insurancePolicy): RedirectResponse
    {
        if ($insurancePolicy->claims()->exists()) {
            $insurancePolicy->update(['is_active' => false]);

            return back()->with('success', 'Policy has claims, so it was deactivated instead of deleted.');
        }

        $insurancePolicy->delete();

        return back()->with('success', 'Insurance policy deleted.');
    }

    /** Array form with date columns as plain Y-m-d (avoids a UTC conversion shifting the day). */
    private function present(InsurancePolicy $policy): array
    {
        return array_merge($policy->toArray(), [
            'valid_from' => $policy->valid_from?->format('Y-m-d'),
            'valid_until' => $policy->valid_until?->format('Y-m-d'),
        ]);
    }

    private function rules(int $tenantId, ?InsurancePolicy $policy = null): array
    {
        return [
            'provider_name' => ['required', 'string', 'max:200'],
            'policy_number' => [
                'required', 'string', 'max:100',
                Rule::unique('insurance_policies', 'policy_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($policy?->id),
            ],
            'group_number' => ['nullable', 'string', 'max:100'],
            'coverage_type' => ['nullable', 'string', 'max:100'],
            'coverage_limit' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'copay_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'deductible_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ];
    }

    private function normalise(array $data): array
    {
        $data['coverage_type'] = $data['coverage_type'] ?? 'individual';
        $data['copay_amount'] = $data['copay_amount'] ?? 0;
        $data['deductible_amount'] = $data['deductible_amount'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
