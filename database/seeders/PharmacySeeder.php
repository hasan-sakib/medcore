<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PharmacySeeder extends Seeder
{
    private const COMMON_MEDICINES = [
        ['name' => 'Paracetamol', 'generic_name' => 'Acetaminophen', 'sku' => 'MED-PARA-500', 'category' => 'analgesic', 'unit_type' => 'tablet', 'strength' => '500mg', 'reorder_level' => 50],
        ['name' => 'Amoxicillin', 'generic_name' => 'Amoxicillin', 'sku' => 'MED-AMOX-250', 'category' => 'antibiotic', 'unit_type' => 'capsule', 'strength' => '250mg', 'reorder_level' => 30],
        ['name' => 'Metformin', 'generic_name' => 'Metformin HCl', 'sku' => 'MED-METF-500', 'category' => 'antidiabetic', 'unit_type' => 'tablet', 'strength' => '500mg', 'reorder_level' => 40],
        ['name' => 'Omeprazole', 'generic_name' => 'Omeprazole', 'sku' => 'MED-OMEP-020', 'category' => 'antacid', 'unit_type' => 'capsule', 'strength' => '20mg', 'reorder_level' => 25],
        ['name' => 'Atorvastatin', 'generic_name' => 'Atorvastatin Calcium', 'sku' => 'MED-ATOR-010', 'category' => 'antihyperlipidemic', 'unit_type' => 'tablet', 'strength' => '10mg', 'reorder_level' => 20],
    ];

    public function run(): void
    {
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            foreach (self::COMMON_MEDICINES as $medicineData) {
                $medicine = Medicine::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $medicineData['sku']],
                    array_merge($medicineData, [
                        'tenant_id' => $tenant->id,
                        'min_stock_level' => 10,
                        'is_active' => true,
                    ])
                );

                // Seed an initial active batch with 6-month expiry
                $batch = MedicineBatch::create([
                    'tenant_id' => $tenant->id,
                    'medicine_id' => $medicine->id,
                    'batch_number' => 'BATCH-SEED-'.strtoupper(substr($medicineData['sku'], 4, 4)).'-001',
                    'quantity_received' => 200,
                    'quantity_on_hand' => 200,
                    'unit_cost' => 0.50,
                    'expiry_date' => now()->addMonths(6)->toDateString(),
                    'status' => 'active',
                ]);

                // Matching stock-in movement (use tenant admin user if available)
                $adminUserId = User::where('tenant_id', $tenant->id)->first()?->id ?? 1;

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'in',
                    'quantity' => 200,
                    'notes' => 'Initial stock seed',
                    'created_by' => $adminUserId,
                ]);
            }
        }
    }
}
