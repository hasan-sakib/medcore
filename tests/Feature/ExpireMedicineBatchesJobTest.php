<?php

use App\Jobs\ExpireMedicineBatchesJob;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Services\InventoryService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenantA = $provisioner->provision(
        ['name' => 'Sweep A', 'slug' => 'sweepa', 'plan' => 'professional'],
        ['name' => 'A Admin', 'email' => 'admin@sweepa.test', 'password' => 'pass']
    );
    $this->tenantB = $provisioner->provision(
        ['name' => 'Sweep B', 'slug' => 'sweepb', 'plan' => 'professional'],
        ['name' => 'B Admin', 'email' => 'admin@sweepb.test', 'password' => 'pass']
    );
});

function sweepMedicineWithBatch($tenant, array $batchState): MedicineBatch
{
    app(TenantManager::class)->setCurrent($tenant);

    $medicine = Medicine::create([
        'name' => 'Sweep Drug '.$tenant->slug.uniqid(),
        'sku' => 'SW-'.strtoupper(uniqid()),
        'unit_type' => 'tablet',
        'reorder_level' => 1,
        'is_active' => true,
    ]);

    return MedicineBatch::factory()->forTenant($tenant)->create(['medicine_id' => $medicine->id] + $batchState);
}

it('marks past-expiry active batches as expired and leaves valid ones alone', function () {
    $old = sweepMedicineWithBatch($this->tenantA, ['expiry_date' => now()->subDay()->toDateString()]);
    $fresh = sweepMedicineWithBatch($this->tenantA, ['expiry_date' => now()->addMonths(3)->toDateString()]);

    (new ExpireMedicineBatchesJob)->handle(app(InventoryService::class), app(TenantManager::class));

    expect(MedicineBatch::withoutTenant()->find($old->id)->status)->toBe('expired')
        ->and(MedicineBatch::withoutTenant()->find($fresh->id)->status)->toBe('active');
});

it('sweeps every tenant independently', function () {
    $a = sweepMedicineWithBatch($this->tenantA, ['expiry_date' => now()->subDays(2)->toDateString()]);
    $b = sweepMedicineWithBatch($this->tenantB, ['expiry_date' => now()->subDays(2)->toDateString()]);

    (new ExpireMedicineBatchesJob)->handle(app(InventoryService::class), app(TenantManager::class));

    expect(MedicineBatch::withoutTenant()->find($a->id)->status)->toBe('expired')
        ->and(MedicineBatch::withoutTenant()->find($b->id)->status)->toBe('expired');
});
