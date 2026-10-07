<?php

namespace Database\Seeders;

use App\Models\ChargeItem;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TaxConfig;
use App\Models\Tenant;
use App\Support\TenantManager;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    private const CHARGE_ITEMS = [
        ['name' => 'General Consultation',    'code' => 'CONS-GEN',   'category' => 'consultation', 'unit_price' => 50.00,  'tax_rate' => 0],
        ['name' => 'Specialist Consultation', 'code' => 'CONS-SPEC',  'category' => 'consultation', 'unit_price' => 120.00, 'tax_rate' => 0],
        ['name' => 'Emergency Consultation',  'code' => 'CONS-EMG',   'category' => 'consultation', 'unit_price' => 80.00,  'tax_rate' => 0],
        ['name' => 'General Ward Bed/Day',    'code' => 'BED-GEN',    'category' => 'bed',           'unit_price' => 40.00,  'tax_rate' => 0],
        ['name' => 'ICU Bed/Day',             'code' => 'BED-ICU',    'category' => 'bed',           'unit_price' => 250.00, 'tax_rate' => 0],
        ['name' => 'Minor Procedure',         'code' => 'PROC-MINOR', 'category' => 'procedure',     'unit_price' => 75.00,  'tax_rate' => 5],
        ['name' => 'Major Procedure',         'code' => 'PROC-MAJOR', 'category' => 'procedure',     'unit_price' => 300.00, 'tax_rate' => 5],
        ['name' => 'CBC Blood Test',          'code' => 'LAB-CBC',    'category' => 'lab',           'unit_price' => 15.00,  'tax_rate' => 0],
        ['name' => 'X-Ray Chest',             'code' => 'RAD-XRAY',   'category' => 'radiology',     'unit_price' => 35.00,  'tax_rate' => 0],
    ];

    public function run(): void
    {
        $manager = app(TenantManager::class);
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            $manager->setCurrent($tenant);

            TaxConfig::create(['name' => 'Standard VAT', 'rate' => 5.00, 'applies_to' => 'all', 'is_active' => true]);

            foreach (self::CHARGE_ITEMS as $item) {
                ChargeItem::create(array_merge($item, ['is_active' => true]));
            }

            $this->seedSampleInvoices($tenant->id);

            $manager->clearCurrent();
        }
    }

    private function seedSampleInvoices(int $tenantId): void
    {
        $patients = Patient::limit(3)->get();
        if ($patients->isEmpty()) {
            return;
        }

        $consultation = ChargeItem::where('category', 'consultation')->first();
        $labItem = ChargeItem::where('category', 'lab')->first();

        foreach ($patients as $i => $patient) {
            $invoice = Invoice::create([
                'patient_id' => $patient->id,
                'invoice_number' => 'INV-SEED-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'status' => $i === 0 ? 'paid' : ($i === 1 ? 'sent' : 'draft'),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'amount_paid' => 0,
                'amount_due' => 0,
                'due_date' => now()->addDays(30)->toDateString(),
                'created_by' => 1,
            ]);

            if ($consultation) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'charge_item_id' => $consultation->id,
                    'description' => $consultation->name,
                    'quantity' => 1,
                    'unit_price' => $consultation->unit_price,
                    'tax_rate' => $consultation->tax_rate,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'line_total' => $consultation->unit_price,
                ]);
            }

            if ($labItem) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'charge_item_id' => $labItem->id,
                    'description' => $labItem->name,
                    'quantity' => 1,
                    'unit_price' => $labItem->unit_price,
                    'tax_rate' => $labItem->tax_rate,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'line_total' => $labItem->unit_price,
                ]);
            }

            $total = ($consultation?->unit_price ?? 0) + ($labItem?->unit_price ?? 0);
            $amountPaid = $i === 0 ? $total : 0;

            $invoice->update([
                'subtotal' => $total,
                'total_amount' => $total,
                'amount_paid' => $amountPaid,
                'amount_due' => $total - $amountPaid,
                'paid_at' => $i === 0 ? now()->subDays(2) : null,
            ]);

            if ($i === 0 && $total > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'patient_id' => $patient->id,
                    'amount' => $total,
                    'payment_method' => 'cash',
                    'recorded_by' => 1,
                    'paid_at' => now()->subDays(2),
                ]);
            }
        }
    }
}
