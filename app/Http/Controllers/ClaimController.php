<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\InsurancePolicy;
use App\Models\Invoice;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClaimController extends Controller
{
    public function index(Request $request): Response
    {
        $claims = Claim::with(['invoice', 'patient', 'insurancePolicy'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Billing/Claims/Index', [
            'claims' => $claims,
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required', TenantRule::exists('invoices', 'id')],
            'insurance_policy_id' => ['required', TenantRule::exists('insurance_policies', 'id')],
            'amount_claimed' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $policy = InsurancePolicy::findOrFail($data['insurance_policy_id']);

        Claim::create([
            'invoice_id' => $invoice->id,
            'insurance_policy_id' => $policy->id,
            'patient_id' => $invoice->patient_id,
            'claim_number' => $this->generateClaimNumber($invoice->tenant_id),
            'status' => 'draft',
            'amount_claimed' => $data['amount_claimed'],
            'notes' => $data['notes'] ?? null,
            'submitted_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Claim created.');
    }

    public function updateStatus(Request $request, Claim $claim): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:draft,submitted,under_review,approved,partially_approved,rejected,paid',
            'amount_approved' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $updates = ['status' => $data['status'], 'notes' => $data['notes'] ?? $claim->notes];

        if ($data['status'] === 'submitted') {
            $updates['submitted_at'] = now();
        }
        if (in_array($data['status'], ['approved', 'partially_approved', 'rejected'])) {
            $updates['reviewed_at'] = now();
            $updates['amount_approved'] = $data['amount_approved'] ?? null;
        }
        if ($data['status'] === 'paid') {
            $updates['amount_paid'] = $data['amount_paid'] ?? $claim->amount_approved;
        }

        $claim->update($updates);

        return back()->with('success', 'Claim status updated.');
    }

    private function generateClaimNumber(int $tenantId): string
    {
        $prefix = 'CLM-'.now()->format('Ym').'-';
        $last = Claim::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where('claim_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('claim_number');

        $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
