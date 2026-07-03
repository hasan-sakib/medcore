<?php

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'FEFO Hospital', 'slug' => 'fefoh', 'plan' => 'professional'],
        ['name' => 'FH Admin', 'email' => 'admin@fefoh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->pharmacist = User::factory()->forTenant($this->tenant)->create();
    $this->pharmacist->assignRole('pharmacist');

    $this->medicine = Medicine::create([
        'name' => 'FEFO Test Drug',
        'sku' => 'MED-FEFO-001',
        'unit_type' => 'tablet',
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $this->inventory = app(InventoryService::class);
});

it('deducts from earliest-expiry batch first (FEFO)', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    // Batch A: expires in 3 months (should be deducted first)
    $batchA = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-A',
        'quantity_received' => 50,
        'quantity_on_hand' => 50,
        'expiry_date' => now()->addMonths(3)->toDateString(),
        'status' => 'active',
    ]);

    // Batch B: expires in 9 months
    $batchB = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-B',
        'quantity_received' => 50,
        'quantity_on_hand' => 50,
        'expiry_date' => now()->addMonths(9)->toDateString(),
        'status' => 'active',
    ]);

    $this->inventory->deductStockFEFO($this->medicine->id, 10, $this->pharmacist->id);

    $batchA->refresh();
    $batchB->refresh();

    expect($batchA->quantity_on_hand)->toBe(40); // deducted first
    expect($batchB->quantity_on_hand)->toBe(50); // untouched
});

it('spans multiple batches when single batch is insufficient', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $batchA = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-SPAN-A',
        'quantity_received' => 3,
        'quantity_on_hand' => 3,
        'expiry_date' => now()->addMonths(2)->toDateString(),
        'status' => 'active',
    ]);

    $batchB = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-SPAN-B',
        'quantity_received' => 10,
        'quantity_on_hand' => 10,
        'expiry_date' => now()->addMonths(8)->toDateString(),
        'status' => 'active',
    ]);

    $records = $this->inventory->deductStockFEFO($this->medicine->id, 8, $this->pharmacist->id);

    $batchA->refresh();
    $batchB->refresh();

    expect($batchA->quantity_on_hand)->toBe(0);
    expect($batchA->status)->toBe('depleted');
    expect($batchB->quantity_on_hand)->toBe(5); // 10 - 5 (remaining after A depleted)
    expect($records)->toHaveCount(2); // two batches touched
});

it('marks batch as depleted when quantity reaches zero', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $batch = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-DEPLETE',
        'quantity_received' => 5,
        'quantity_on_hand' => 5,
        'expiry_date' => now()->addMonths(4)->toDateString(),
        'status' => 'active',
    ]);

    $this->inventory->deductStockFEFO($this->medicine->id, 5, $this->pharmacist->id);

    $batch->refresh();
    expect($batch->quantity_on_hand)->toBe(0);
    expect($batch->status)->toBe('depleted');
});

it('throws RuntimeException when stock is insufficient', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-LOW',
        'quantity_received' => 2,
        'quantity_on_hand' => 2,
        'expiry_date' => now()->addMonths(4)->toDateString(),
        'status' => 'active',
    ]);

    expect(fn () => $this->inventory->deductStockFEFO($this->medicine->id, 10, $this->pharmacist->id))
        ->toThrow(RuntimeException::class, 'Insufficient stock');
});

it('does not deduct from expired batches', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-EXPIRED',
        'quantity_received' => 100,
        'quantity_on_hand' => 100,
        'expiry_date' => now()->subDay()->toDateString(),
        'status' => 'expired',
    ]);

    expect(fn () => $this->inventory->deductStockFEFO($this->medicine->id, 1, $this->pharmacist->id))
        ->toThrow(RuntimeException::class, 'Insufficient stock');
});
