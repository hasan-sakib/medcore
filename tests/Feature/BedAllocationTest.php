<?php

use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\User;
use App\Models\Ward;
use App\Services\BedAllocationService;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

beforeEach(function () {
    $this->tenant = app(TenantProvisioningService::class)->provision(
        ['name' => 'Bed Hospital', 'slug' => 'bedh', 'plan' => 'professional'],
        ['name' => 'BH Admin', 'email' => 'admin@bedh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->nurse = User::factory()->forTenant($this->tenant)->create();
    $this->nurse->assignRole('nurse');

    $this->admin = User::factory()->forTenant($this->tenant)->create();
    $this->admin->assignRole('tenant-admin');

    $this->ward = Ward::create(['name' => 'General Ward', 'code' => 'GEN', 'ward_type' => 'general', 'is_active' => true]);

    $this->bed = Bed::create([
        'ward_id' => $this->ward->id,
        'bed_number' => 'B-101',
        'bed_type' => 'standard',
        'status' => 'available',
        'is_active' => true,
    ]);

    $patients = app(PatientService::class);
    $this->actingAs($this->nurse);
    $this->patientA = $patients->create(['first_name' => 'Ann', 'last_name' => 'One', 'date_of_birth' => '1980-01-01']);
    $this->patientB = $patients->create(['first_name' => 'Bob', 'last_name' => 'Two', 'date_of_birth' => '1981-02-02']);
});

it('admits a patient and marks the bed occupied', function () {
    $this->actingAs($this->nurse);

    $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientA->id,
    ])->assertSessionHasNoErrors();

    expect($this->bed->fresh()->status)->toBe('occupied');
    expect(BedAllocation::where('bed_id', $this->bed->id)->whereNull('discharged_at')->count())->toBe(1);
});

it('lets exactly one of two sequential requests for the same bed win', function () {
    $this->actingAs($this->nurse);

    $first = $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientA->id,
    ]);
    $first->assertSessionHasNoErrors();

    $second = $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientB->id,
    ]);
    $second->assertSessionHasErrors('bed_id');

    $active = BedAllocation::where('bed_id', $this->bed->id)->whereNull('discharged_at')->get();
    expect($active)->toHaveCount(1);
    expect($active->first()->patient_id)->toBe($this->patientA->id);
    expect($this->bed->fresh()->status)->toBe('occupied');
});

it('refuses to admit to a bed under maintenance', function () {
    $this->bed->update(['status' => 'maintenance']);
    $this->actingAs($this->nurse);

    $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientA->id,
    ])->assertSessionHasErrors('bed_id');

    expect(BedAllocation::where('bed_id', $this->bed->id)->count())->toBe(0);
});

it('discharge closes the allocation, puts the bed in cleaning, and marking available frees it for re-admission', function () {
    $service = app(BedAllocationService::class);
    $allocation = $service->admit($this->bed, $this->patientA, null, $this->nurse);

    $this->actingAs($this->admin);
    $this->patch("http://bedh.medcore.local/bed-allocations/{$allocation->id}/discharge", [
        'discharge_reason' => 'Recovered',
    ])->assertSessionHasNoErrors();

    $allocation->refresh();
    expect($allocation->discharged_at)->not->toBeNull();
    expect($allocation->discharge_reason)->toBe('Recovered');
    expect($this->bed->fresh()->status)->toBe('cleaning');

    // A bed being cleaned cannot be admitted to yet.
    $this->actingAs($this->nurse);
    $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientB->id,
    ])->assertSessionHasErrors('bed_id');

    // Once cleaning is done the bed is free again.
    $this->actingAs($this->admin);
    $this->post("http://bedh.medcore.local/beds/{$this->bed->id}/available")->assertSessionHasNoErrors();
    expect($this->bed->fresh()->status)->toBe('available');

    $this->actingAs($this->nurse);
    $this->post('http://bedh.medcore.local/bed-allocations', [
        'bed_id' => $this->bed->id,
        'patient_id' => $this->patientB->id,
    ])->assertSessionHasNoErrors();
    expect($this->bed->fresh()->status)->toBe('occupied');
});

it('toggles maintenance on and off', function () {
    $this->actingAs($this->admin);

    $this->post("http://bedh.medcore.local/beds/{$this->bed->id}/maintenance")->assertSessionHasNoErrors();
    expect($this->bed->fresh()->status)->toBe('maintenance');

    $this->post("http://bedh.medcore.local/beds/{$this->bed->id}/maintenance")->assertSessionHasNoErrors();
    expect($this->bed->fresh()->status)->toBe('available');
});

it('a user without bed-allocations.edit cannot toggle maintenance or discharge', function () {
    $allocation = app(BedAllocationService::class)->admit($this->bed, $this->patientA, null, $this->nurse);

    $this->actingAs($this->nurse);

    $this->post("http://bedh.medcore.local/beds/{$this->bed->id}/maintenance")->assertForbidden();
    $this->patch("http://bedh.medcore.local/bed-allocations/{$allocation->id}/discharge", [
        'discharge_reason' => 'x',
    ])->assertForbidden();

    expect($this->bed->fresh()->status)->toBe('occupied');
    expect($allocation->fresh()->discharged_at)->toBeNull();
});
