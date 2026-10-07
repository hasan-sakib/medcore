<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request): Response
    {
        $patient = $request->user()->patient;

        $invoices = Invoice::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Portal/Invoices/Index', [
            'invoices' => $invoices,
            'summary' => [
                'total_paid' => Invoice::where('patient_id', $patient->id)->where('status', 'paid')->sum('total_amount'),
                'total_due' => Invoice::where('patient_id', $patient->id)->whereIn('status', ['sent', 'partially_paid'])->sum('amount_due'),
            ],
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $patient = $request->user()->patient;
        abort_if($invoice->patient_id !== $patient->id, 403);

        $invoice->loadMissing(['lines.chargeItem', 'payments']);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(Request $request, Invoice $invoice): StreamedResponse|RedirectResponse
    {
        $patient = $request->user()->patient;
        abort_if($invoice->patient_id !== $patient->id, 403);

        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            $path = $this->invoiceService->generatePdf($invoice);
        } else {
            $path = $invoice->pdf_path;
        }

        return Storage::disk('local')->download($path, $invoice->invoice_number.'.pdf');
    }
}
