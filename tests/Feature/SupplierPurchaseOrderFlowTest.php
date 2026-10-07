<?php

use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $this->tenant = app(TenantProvisioningService::class)->provision(
        ['name' => 'PO Hospital', 'slug' => 'poh', 'plan' => 'professional'],
        ['name' => 'PO Admin', 'email' => 'admin@poh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $user = User::factory()->forTenant($this->tenant)->create();
    $user->assignRole('tenant-admin');
    $this->admin = $user->fresh();
    $this->actingAs($this->admin);

    $this->medicine = Medicine::create([
        'name' => 'PO Test Drug',
        'sku' => 'MED-PO-001',
        'unit_type' => 'tablet',
        'reorder_level' => 10,
        'is_active' => true,
    ]);
});

it('creates a supplier and renders the supplier pages', function () {
    $this->post('http://poh.medcore.local/admin/suppliers', [
        'name' => 'Acme Pharma',
        'contact_name' => 'Jane Doe',
        'email' => 'sales@acme.test',
    ])->assertRedirect('/admin/suppliers');

    $supplier = Supplier::where('name', 'Acme Pharma')->firstOrFail();

    $this->get('http://poh.medcore.local/admin/suppliers')
        ->assertOk()->assertInertia(fn ($page) => $page->component('Suppliers/Index'));
    $this->get('http://poh.medcore.local/admin/suppliers/create')
        ->assertOk()->assertInertia(fn ($page) => $page->component('Suppliers/Create'));
    $this->get("http://poh.medcore.local/admin/suppliers/{$supplier->id}/edit")
        ->assertOk()->assertInertia(fn ($page) => $page->component('Suppliers/Edit'));
    $this->get("http://poh.medcore.local/admin/suppliers/{$supplier->id}")
        ->assertRedirect("/admin/suppliers/{$supplier->id}/edit");
});

it('rejects a supplier without a name', function () {
    $this->post('http://poh.medcore.local/admin/suppliers', ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('creates a purchase order with items and updates its status', function () {
    $supplier = Supplier::create(['name' => 'Acme Pharma', 'is_active' => true]);

    $this->get('http://poh.medcore.local/admin/purchase-orders/create')
        ->assertOk()->assertInertia(fn ($page) => $page->component('PurchaseOrders/Create'));

    $this->post('http://poh.medcore.local/admin/purchase-orders', [
        'supplier_id' => $supplier->id,
        'items' => [
            ['medicine_id' => $this->medicine->id, 'quantity_ordered' => 50, 'unit_price' => '1.25'],
        ],
    ])->assertRedirect('/admin/purchase-orders');

    $po = PurchaseOrder::with('items')->firstOrFail();
    expect($po->status)->toBe('draft');
    expect($po->items)->toHaveCount(1);
    expect($po->items->first()->quantity_ordered)->toBe(50);

    $this->get('http://poh.medcore.local/admin/purchase-orders')
        ->assertOk()->assertInertia(fn ($page) => $page->component('PurchaseOrders/Index'));
    $this->get("http://poh.medcore.local/admin/purchase-orders/{$po->id}")
        ->assertOk()->assertInertia(fn ($page) => $page->component('PurchaseOrders/Show'));

    $this->patch("http://poh.medcore.local/admin/purchase-orders/{$po->id}", ['status' => 'sent'])
        ->assertRedirect("/admin/purchase-orders/{$po->id}");
    expect($po->fresh()->status)->toBe('sent');
    expect($po->fresh()->ordered_at)->not->toBeNull();
});

it('rejects a purchase order with no items', function () {
    $this->post('http://poh.medcore.local/admin/purchase-orders', ['items' => []])
        ->assertSessionHasErrors('items');
});
