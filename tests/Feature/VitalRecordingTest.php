<?php

use App\Models\Department;
use App\Models\Encounter;
use App\Models\User;
use App\Models\Vital;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Vitals Hospital', 'slug' => 'vitalsh', 'plan' => 'professional'],
        ['name' => 'VH Admin', 'email' => 'admin@vitalsh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->department = Department::create(['name' => 'Ward', 'code' => 'WRD']);

    $this->doctor = User::factory()->forTenant($this->tenant)->create();
    $this->doctor->assignRole('doctor');

    $this->nurse = User::factory()->forTenant($this->tenant)->create();
    $this->nurse->assignRole('nurse');

    $this->actingAs($this->doctor);
    $patientService = app(PatientService::class);
    $this->patient = $patientService->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'date_of_birth' => '1975-04-20',
    ]);

    $this->encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);
});

it('nurse can record vitals for an encounter', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->nurse);

    $response = $this->post(
        "http://vitalsh.medcore.local/encounters/{$this->encounter->id}/vitals",
        [
            'temperature_c' => 37.2,
            'pulse_bpm' => 72,
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'spo2_pct' => 98.5,
        ]
    );

    $response->assertRedirect();

    $vital = Vital::where('encounter_id', $this->encounter->id)->first();
    expect($vital)->not->toBeNull();
    expect($vital->recorded_by)->toBe($this->nurse->id);
    expect((float) $vital->temperature_c)->toBe(37.2);
    expect($vital->pulse_bpm)->toBe(72);
});

it('auto-computes BMI when weight and height are provided', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->nurse);

    $this->post(
        "http://vitalsh.medcore.local/encounters/{$this->encounter->id}/vitals",
        [
            'weight_kg' => 70.0,
            'height_cm' => 175.0,
        ]
    );

    $vital = Vital::where('encounter_id', $this->encounter->id)->first();

    // BMI = 70 / (1.75 ^ 2) = 22.9
    expect((float) $vital->bmi)->toBe(22.9);
});

it('rejects temperature_c outside valid range', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->nurse);

    $response = $this->post(
        "http://vitalsh.medcore.local/encounters/{$this->encounter->id}/vitals",
        ['temperature_c' => 99.0] // way too high
    );

    $response->assertSessionHasErrors('temperature_c');
});

it('vitals are NOT encrypted — raw DB value is numeric', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->nurse);

    $this->post(
        "http://vitalsh.medcore.local/encounters/{$this->encounter->id}/vitals",
        ['temperature_c' => 36.8, 'pulse_bpm' => 68]
    );

    $raw = DB::table('vitals')
        ->where('encounter_id', $this->encounter->id)
        ->first();

    // Raw values should be numeric, not ciphertext
    expect((float) $raw->temperature_c)->toBe(36.8);
    expect((int) $raw->pulse_bpm)->toBe(68);
});

it('vital belongs to the correct tenant', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->nurse);

    $this->post(
        "http://vitalsh.medcore.local/encounters/{$this->encounter->id}/vitals",
        ['pulse_bpm' => 75]
    );

    $vital = Vital::where('encounter_id', $this->encounter->id)->first();
    expect($vital->tenant_id)->toBe($this->tenant->id);
});
