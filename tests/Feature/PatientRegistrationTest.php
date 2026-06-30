<?php

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Provision two demo tenants with proper roles/permissions
    $provisioner = app(TenantProvisioningService::class);

    $this->tenantA = $provisioner->provision(
        ['name' => 'Hospital A', 'slug' => 'hospitala', 'plan' => 'professional'],
        ['name' => 'Admin A', 'email' => 'admin@hospitala.test', 'password' => 'pass']
    );

    $this->tenantB = $provisioner->provision(
        ['name' => 'Hospital B', 'slug' => 'hospitalb', 'plan' => 'professional'],
        ['name' => 'Admin B', 'email' => 'admin@hospitalb.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenantA);

    // Create a receptionist and doctor in Tenant A
    $this->receptionist = User::factory()->forTenant($this->tenantA)->create();
    $this->receptionist->assignRole('receptionist');

    $this->doctor = User::factory()->forTenant($this->tenantA)->create();
    $this->doctor->assignRole('doctor');
});

it('receptionist can register a patient via service', function () {
    app(TenantManager::class)->setCurrent($this->tenantA);

    $service = app(PatientService::class);

    // Authenticate as receptionist so registered_by is set correctly
    $this->actingAs($this->receptionist);

    $patient = $service->create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'date_of_birth' => '1906-12-09',
        'gender' => 'female',
    ]);

    expect($patient->tenant_id)->toBe($this->tenantA->id);
    expect($patient->registered_by)->toBe($this->receptionist->id);
    expect($patient->first_name)->toBe('Grace');
    expect($patient->mrn)->toStartWith('MRN-');
});

it('PHI fields are encrypted in the database', function () {
    app(TenantManager::class)->setCurrent($this->tenantA);
    $this->actingAs($this->receptionist);

    $service = app(PatientService::class);
    $patient = $service->create([
        'first_name' => 'Linus',
        'last_name' => 'Torvalds',
        'date_of_birth' => '1969-12-28',
    ]);

    $raw = DB::table('patients')->where('id', $patient->id)->value('first_name');

    expect($raw)->not->toBe('Linus')
        ->and($raw)->not->toBeNull();
});

it('tenant A patient is invisible from tenant B context', function () {
    app(TenantManager::class)->setCurrent($this->tenantA);
    $this->actingAs($this->receptionist);

    $service = app(PatientService::class);
    $service->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'date_of_birth' => '1815-12-10',
    ]);

    // Switch to Tenant B — must not see Tenant A's patient
    app(TenantManager::class)->setCurrent($this->tenantB);

    $count = Patient::count();
    expect($count)->toBe(0);
});

it('logs phi_read:patient_profile on show request', function () {
    app(TenantManager::class)->setCurrent($this->tenantA);
    $this->actingAs($this->receptionist);

    $service = app(PatientService::class);
    $patient = $service->create([
        'first_name' => 'Marie',
        'last_name' => 'Curie',
        'date_of_birth' => '1867-11-07',
    ]);

    // Hit the show endpoint through HTTP
    $response = $this->get("http://hospitala.medcore.local/patients/{$patient->id}");
    $response->assertOk();

    $log = AuditLog::where('auditable_type', Patient::class)
        ->where('auditable_id', $patient->id)
        ->where('action', 'phi_read:patient_profile')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->receptionist->id);
});

it('doctor cannot delete a patient (tenant-admin only)', function () {
    app(TenantManager::class)->setCurrent($this->tenantA);
    $this->actingAs($this->receptionist);

    $service = app(PatientService::class);
    $patient = $service->create([
        'first_name' => 'Alan',
        'last_name' => 'Turing',
        'date_of_birth' => '1912-06-23',
    ]);

    $this->actingAs($this->doctor);

    $response = $this->delete("http://hospitala.medcore.local/patients/{$patient->id}");
    $response->assertForbidden();
});
