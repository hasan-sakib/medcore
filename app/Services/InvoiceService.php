<?php

namespace App\Services;

use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Encounter;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function createDraft(Patient $patient, int $userId, ?int $encounterId = null): Invoice
    {
        return Invoice::create([
            'patient_id'   => $patient->id,
            'encounter_id' => $encounterId,
            'invoice_number' => $this->generateInvoiceNumber($patient->tenant_id),
            'status'       => 'draft',
            'subtotal'     => 0,
            'tax_total'    => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'amount_paid'  => 0,
            'amount_due'   => 0,
            'due_date'     => now()->addDays(30)->toDateString(),
            'created_by'   => $userId,
        ]);
    }

    public function addLine(Invoice $invoice, array $data): InvoiceLine
    {
        $qty        = (float) ($data['quantity'] ?? 1);
        $unitPrice  = (float) $data['unit_price'];
        $taxRate    = (float) ($data['tax_rate'] ?? 0);
        $discount   = (float) ($data['discount_amount'] ?? 0);
        $taxAmount  = round(($qty * $unitPrice - $discount) * $taxRate / 100, 2);
        $lineTotal  = round($qty * $unitPrice - $discount + $taxAmount, 2);

        $line = InvoiceLine::create([
            'invoice_id'     => $invoice->id,
            'charge_item_id' => $data['charge_item_id'] ?? null,
            'description'    => $data['description'],
            'quantity'       => $qty,
            'unit_price'     => $unitPrice,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'discount_amount'=> $discount,
            'line_total'     => $lineTotal,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id'   => $data['reference_id'] ?? null,
        ]);

        $this->recalculate($invoice);

        return $line;
    }

    public function recalculate(Invoice $invoice): void
    {
        $lines    = $invoice->lines()->get();
        $subtotal = $lines->sum(fn ($l) => $l->quantity * $l->unit_price - $l->discount_amount);
        $taxTotal = $lines->sum('tax_amount');
        $total    = $subtotal + $taxTotal - $invoice->discount_amount;
        $amountDue = max(0, $total - $invoice->amount_paid);

        $invoice->update([
            'subtotal'     => round($subtotal, 2),
            'tax_total'    => round($taxTotal, 2),
            'total_amount' => round($total, 2),
            'amount_due'   => round($amountDue, 2),
        ]);
    }

    public function finalize(Invoice $invoice): Invoice
    {
        $this->recalculate($invoice->fresh());
        $invoice->update(['status' => 'sent']);
        GenerateInvoicePdfJob::dispatch($invoice->id);
        return $invoice->fresh();
    }

    public function recordPayment(Invoice $invoice, array $data, int $userId): void
    {
        DB::transaction(function () use ($invoice, $data, $userId) {
            $invoice->payments()->create([
                'patient_id'      => $invoice->patient_id,
                'amount'          => $data['amount'],
                'payment_method'  => $data['payment_method'],
                'reference_number'=> $data['reference_number'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'recorded_by'     => $userId,
                'paid_at'         => now(),
            ]);

            $totalPaid = $invoice->payments()->sum('amount');
            $amountDue = max(0, $invoice->total_amount - $totalPaid);
            $status    = $amountDue <= 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due'  => $amountDue,
                'status'      => $status,
                'paid_at'     => $amountDue <= 0 ? now() : null,
            ]);
        });
    }

    public function generatePdf(Invoice $invoice): string
    {
        $invoice->loadMissing(['patient', 'lines.chargeItem', 'payments', 'createdBy']);

        $pdf  = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        $path = "invoices/{$invoice->tenant_id}/{$invoice->invoice_number}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    private function generateInvoiceNumber(int $tenantId): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';

        $last = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq  = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
