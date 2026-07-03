<?php

use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\User;
use App\Services\PatientService;
use App\Services\PrescriptionService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'RX Hospital', 'slug' => 'rxh', 'plan' => 'professional'],
        ['name' => 'RX Admin', 'email' => 'admin@rxh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->doctor = User::factory()->forTenant($this->tenant)->create();
    $this->doctor->assignRole('doctor');

    $this->pharmacist = User::factory()->forTenant($this->tenant)->create();
    $this->pharmacist->assignRole('pharmacist');

    $this->actingAs($this->doctor);
    $this->patient = app(PatientService::class)->create([
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'date_of_birth' => '1985-06-15',
    ]);

    $this->medicine1 = Medicine::create([
        'name' => 'Drug A',
        'sku' => 'MED-RX-001',
        'unit_type' => 'tablet',
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $this->medicine2 = Medicine::create([
        'name' => 'Drug B',
        'sku' => 'MED-RX-002',
        'unit_type' => 'capsule',
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    MedicineBatch::create([
        'medicine_id' => $this->medicine1->id,
        'batch_number' => 'BATCH-RXA-001',
        'quantity_received' => 100,
        'quantity_on_hand' => 100,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    MedicineBatch::create([
        'medicine_id' => $this->medicine2->id,
        'batch_number' => 'BATCH-RXB-001',
        'quantity_received' => 100,
        'quantity_on_hand' => 100,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    $this->rxService = app(PrescriptionService::class);

    $this->prescription = $this->rxService->create([
        'patient_id' => $this->patient->id,
        'items' => [
            [
                'medicine_id' => $this->medicine1->id,
                'dosage_instruction' => '1 tablet daily',
                'frequency' => 'OD',
                'quantity_prescribed' => 7,
            ],
            [
                'medicine_id' => $this->medicine2->id,
                'dosage_instruction' => '1 capsule twice daily',
                'frequency' => 'BD',
                'quantity_prescribed' => 14,
            ],
        ],
    ]);
});

it('prescription status is partially_filled after first item dispensed', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $firstItem = $this->prescription->items->first();
    $this->rxService->fillItem($firstItem, $this->pharmacist->id);

    $this->prescription->refresh();
    expect($this->prescription->status)->toBe('partially_filled');
});

it('prescription status becomes filled after all items dispensed', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    foreach ($this->prescription->items as $item) {
        $this->rxService->fillItem($item, $this->pharmacist->id);
    }

    $this->prescription->refresh();
    expect($this->prescription->status)->toBe('filled');
});

it('cancelled prescription cannot be dispensed', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $this->rxService->cancel($this->prescription, 'Patient refused');
    $item = $this->prescription->items->first();

    expect(fn () => $this->rxService->fillItem($item, $this->pharmacist->id))
        ->toThrow(RuntimeException::class);
});

it('audit log records prescription creation', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $log = AuditLog::where('auditable_type', 'App\Models\Prescription')
        ->where('auditable_id', $this->prescription->id)
        ->where('action', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->tenant_id)->toBe($this->tenant->id);
});

it('doctor can create prescription via HTTP endpoint', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->doctor);

    $response = $this->post('http://rxh.medcore.local/prescriptions', [
        'patient_id' => $this->patient->id,
        'items' => [
            [
                'medicine_id' => $this->medicine1->id,
                'dosage_instruction' => '2 tablets daily',
                'frequency' => 'OD',
                'quantity_prescribed' => 14,
            ],
        ],
    ]);

    $response->assertRedirect();

    $newRx = Prescription::where('patient_id', $this->patient->id)
        ->where('prescribed_by', $this->doctor->id)
        ->latest('id')
        ->first();

    expect($newRx)->not->toBeNull();
    expect($newRx->items)->toHaveCount(1);
});
