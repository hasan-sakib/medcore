<?php

use App\Models\DispenseRecord;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Dispense Hospital', 'slug' => 'disph', 'plan' => 'professional'],
        ['name' => 'DH Admin', 'email' => 'admin@disph.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->doctor = User::factory()->forTenant($this->tenant)->create();
    $this->doctor->assignRole('doctor');

    $this->pharmacist = User::factory()->forTenant($this->tenant)->create();
    $this->pharmacist->assignRole('pharmacist');

    $this->actingAs($this->doctor);
    $this->patient = app(PatientService::class)->create([
        'first_name' => 'John',
        'last_name' => 'Smith',
        'date_of_birth' => '1980-01-01',
    ]);

    $this->medicine = Medicine::create([
        'name' => 'Test Paracetamol',
        'sku' => 'MED-TST-001',
        'unit_type' => 'tablet',
        'reorder_level' => 10,
        'is_active' => true,
    ]);

    $this->batch = MedicineBatch::create([
        'medicine_id' => $this->medicine->id,
        'batch_number' => 'BATCH-TST-001',
        'quantity_received' => 100,
        'quantity_on_hand' => 100,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    $this->prescription = Prescription::create([
        'patient_id' => $this->patient->id,
        'prescribed_by' => $this->doctor->id,
        'status' => 'pending',
    ]);

    $this->item = PrescriptionItem::create([
        'prescription_id' => $this->prescription->id,
        'medicine_id' => $this->medicine->id,
        'dosage_instruction' => '1 tablet twice daily',
        'frequency' => 'BD',
        'quantity_prescribed' => 14,
        'quantity_dispensed' => 0,
    ]);
});

it('pharmacist can dispense a prescription item', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    $response = $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $record = DispenseRecord::where('prescription_item_id', $this->item->id)->first();
    expect($record)->not->toBeNull();
    expect($record->quantity_dispensed)->toBe(14);
    expect($record->dispensed_by)->toBe($this->pharmacist->id);
});

it('dispensing reduces batch quantity_on_hand', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $this->batch->refresh();
    expect($this->batch->quantity_on_hand)->toBe(86); // 100 - 14
});

it('dispensing creates a stock movement of type out', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $movement = StockMovement::where('medicine_id', $this->medicine->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->movement_type)->toBe('out');
    expect($movement->quantity)->toBe(-14);
});

it('out-of-stock returns redirect with error', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    // Deplete the batch
    $this->batch->update(['quantity_on_hand' => 0, 'status' => 'depleted']);

    $response = $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['error']);
});

it('prescription status becomes filled after all items dispensed', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $this->prescription->refresh();
    expect($this->prescription->status)->toBe('filled');
});

it('filled prescription cannot be dispensed again', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->pharmacist);

    // Dispense once
    $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    // Try again — item already fully dispensed
    $response = $this->post('http://disph.medcore.local/pharmacy/dispense', [
        'prescription_item_id' => $this->item->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['error']);
});
