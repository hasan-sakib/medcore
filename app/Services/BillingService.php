<?php

namespace App\Services;

use App\Models\ChargeItem;
use App\Models\Encounter;
use App\Models\Invoice;

class BillingService
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * Build a draft invoice pre-populated with charges from an encounter
     * (consultation fee, dispensed medicines, bed occupancy).
     */
    public function createFromEncounter(Encounter $encounter, int $userId): Invoice
    {
        $invoice = $this->invoiceService->createDraft(
            $encounter->patient,
            $userId,
            $encounter->id,
        );

        // Consultation charge item
        $consultation = ChargeItem::where('category', 'consultation')->where('is_active', true)->first();
        if ($consultation) {
            $this->invoiceService->addLine($invoice, [
                'charge_item_id' => $consultation->id,
                'description' => 'Consultation: '.$consultation->name,
                'quantity' => 1,
                'unit_price' => $consultation->unit_price,
                'tax_rate' => $consultation->tax_rate,
                'reference_type' => 'encounters',
                'reference_id' => $encounter->id,
            ]);
        }

        // Dispense records for this encounter
        $dispenses = $encounter->patient
            ->dispenseRecords()
            ->whereHas('prescription', fn ($q) => $q->where('encounter_id', $encounter->id))
            ->with('medicine')
            ->get();

        foreach ($dispenses as $dispense) {
            $medicineItem = ChargeItem::where('category', 'medicine')
                ->where('name', $dispense->medicine->name)
                ->where('is_active', true)
                ->first();

            $unitPrice = $medicineItem?->unit_price ?? 0;
            $taxRate = $medicineItem?->tax_rate ?? 0;

            if ($unitPrice > 0) {
                $this->invoiceService->addLine($invoice, [
                    'charge_item_id' => $medicineItem?->id,
                    'description' => 'Medicine: '.$dispense->medicine->name,
                    'quantity' => $dispense->quantity_dispensed,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'reference_type' => 'dispense_records',
                    'reference_id' => $dispense->id,
                ]);
            }
        }

        return $invoice->fresh(['lines']);
    }
}
