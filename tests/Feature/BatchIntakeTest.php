<?php

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'GRN Hospital', 'slug' => 'grnh', 'plan' => 'professional'],
        ['name' => 'GH Admin', 'email' => 'admin@grnh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->pharmacist = User::factory()->forTenant($this->tenant)->create();
    $this->pharmacist->assignRole('pharmacist');
    $this->actingAs($this->pharmacist);

    $this->medicine = Medicine::create([
        'name' => 'GRN Test Drug',
        'sku' => 'MED-GRN-001',
        'unit_type' => 'tablet',
        'reorder_level' => 10,
        'is_active' => true,
    ]);

    $this->inventory = app(InventoryService::class);
});

it('pharmacist records batch receipt creating a batch and stock-in movement', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $batch = $this->inventory->recordBatchIntake([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-GRN-001',
        'quantity' => 200,
        'expiry_date' => now()->addMonths(12)->toDateString(),
    ]);

    expect($batch->id)->toBeInt();
    expect($batch->quantity_on_hand)->toBe(200);
    expect($batch->status)->toBe('active');

    $movement = StockMovement::where('batch_id', $batch->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->movement_type)->toBe('in');
    expect($movement->quantity)->toBe(200);
});

it('rejects batch intake via HTTP when expiry_date is in the past', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $response = $this->post('http://grnh.medcore.local/medicine-batches', [
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-EXPIRED',
        'quantity' => 100,
        'expiry_date' => now()->subDay()->toDateString(),
    ]);

    $response->assertSessionHasErrors('expiry_date');
});

it('updates PurchaseOrderItem quantity_received when batch linked to PO', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $supplier = Supplier::create([
        'name' => 'Test Pharma Ltd',
        'is_active' => true,
    ]);

    $adminUser = User::where('tenant_id', $this->tenant->id)->first();

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-TEST-001',
        'status' => 'sent',
        'created_by' => $adminUser->id,
        'ordered_at' => now(),
    ]);

    $poItem = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'medicine_id' => $this->medicine->id,
        'quantity_ordered' => 300,
        'quantity_received' => 0,
        'unit_price' => 0.75,
    ]);

    $this->inventory->recordBatchIntake([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-PO-001',
        'quantity' => 150,
        'expiry_date' => now()->addMonths(10)->toDateString(),
        'purchase_order_id' => $po->id,
    ]);

    $poItem->refresh();
    expect($poItem->quantity_received)->toBe(150);
});

it('batch intake increases the medicine effective stock level', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    // Start: 0 stock
    $initial = MedicineBatch::where('medicine_id', $this->medicine->id)
        ->where('status', 'active')
        ->sum('quantity_on_hand');

    expect($initial)->toBe(0);

    $this->inventory->recordBatchIntake([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-STOCK-001',
        'quantity' => 75,
        'expiry_date' => now()->addMonths(8)->toDateString(),
    ]);

    $afterStock = MedicineBatch::where('medicine_id', $this->medicine->id)
        ->where('status', 'active')
        ->sum('quantity_on_hand');

    expect($afterStock)->toBe(75);
});
