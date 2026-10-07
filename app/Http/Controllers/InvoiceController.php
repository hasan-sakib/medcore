<?php

namespace App\Http\Controllers;

use App\Models\ChargeItem;
use App\Models\Encounter;
use App\Models\InsurancePolicy;
use App\Models\Invoice;
use App\Models\Patient;
use App\Services\BillingService;
use App\Services\InvoiceService;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private BillingService $billingService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Invoice::with(['patient'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, function ($q, $s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('mrn', 'like', "%{$s}%"));
            })
            ->orderByDesc('created_at');

        return Inertia::render('Billing/Invoices/Index', [
            'invoices' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
            'summary' => [
                'total_due' => Invoice::whereIn('status', ['sent', 'partially_paid'])->sum('amount_due'),
                'total_paid' => Invoice::where('status', 'paid')->whereMonth('paid_at', now())->sum('total_amount'),
                'draft_count' => Invoice::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $patients = Patient::select('id', 'mrn', 'first_name', 'last_name')->get();
        $encounters = $request->patient_id
            ? Encounter::where('patient_id', $request->patient_id)
                ->whereDoesntHave('invoices')
                ->select('id', 'encounter_date', 'encounter_type', 'chief_complaint')
                ->orderByDesc('encounter_date')->get()
            : collect();

        return Inertia::render('Billing/Invoices/Create', [
            'patients' => $patients,
            'encounters' => $encounters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', TenantRule::exists('patients', 'id')],
            'encounter_id' => ['nullable', TenantRule::exists('encounters', 'id')],
            'from_encounter' => 'boolean',
        ]);

        if ($data['from_encounter'] ?? false) {
            $encounter = Encounter::findOrFail($data['encounter_id']);
            $invoice = $this->billingService->createFromEncounter($encounter, $request->user()->id);
        } else {
            $patient = Patient::findOrFail($data['patient_id']);
            $invoice = $this->invoiceService->createDraft($patient, $request->user()->id, $data['encounter_id'] ?? null);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->loadMissing(['patient', 'encounter', 'lines.chargeItem', 'payments.recordedBy', 'claims.insurancePolicy']);
        $chargeItems = ChargeItem::where('is_active', true)->get();
        $policies = InsurancePolicy::where('patient_id', $invoice->patient_id)
            ->where('is_active', true)->get();

        return Inertia::render('Billing/Invoices/Show', [
            'invoice' => $invoice,
            'chargeItems' => $chargeItems,
            'policies' => $policies,
        ]);
    }

    public function addLine(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isPaid(), 403, 'Cannot modify a paid invoice.');

        $data = $request->validate([
            'charge_item_id' => ['nullable', TenantRule::exists('charge_items', 'id')],
            'description' => 'required|string|max:300',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $this->invoiceService->addLine($invoice, $data);

        return back()->with('success', 'Line item added.');
    }

    public function finalize(Invoice $invoice): RedirectResponse
    {
        abort_if(! in_array($invoice->status, ['draft']), 403);
        $this->invoiceService->finalize($invoice);

        return back()->with('success', 'Invoice finalized and PDF queued.');
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isPaid(), 403, 'Invoice already paid.');

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$invoice->amount_due,
            'payment_method' => 'required|in:cash,card,bank_transfer,insurance,mobile_money,cheque',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $this->invoiceService->recordPayment($invoice, $data, $request->user()->id);

        return back()->with('success', 'Payment recorded.');
    }

    public function downloadPdf(Invoice $invoice): StreamedResponse|RedirectResponse
    {
        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            $path = $this->invoiceService->generatePdf($invoice);
        } else {
            $path = $invoice->pdf_path;
        }

        return Storage::disk('local')->download($path, $invoice->invoice_number.'.pdf');
    }
}
