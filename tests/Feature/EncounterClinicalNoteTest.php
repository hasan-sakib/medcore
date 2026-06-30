<?php

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicalNote;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Encounter Hospital', 'slug' => 'encounterh', 'plan' => 'professional'],
        ['name' => 'EH Admin', 'email' => 'admin@encounterh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->department = Department::create(['name' => 'Outpatient', 'code' => 'OPD']);

    $this->doctor = User::factory()->forTenant($this->tenant)->create();
    $this->doctor->assignRole('doctor');

    $this->receptionist = User::factory()->forTenant($this->tenant)->create();
    $this->receptionist->assignRole('receptionist');

    $this->actingAs($this->receptionist);
    $patientService = app(PatientService::class);
    $this->patient = $patientService->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1980-06-15',
    ]);
});

it('opening an encounter linked to an appointment updates appointment to checked_in', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $appointment = Appointment::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => now()->addHours(1),
        'ends_at' => now()->addHours(1)->addMinutes(15),
        'status' => 'confirmed',
    ]);

    $this->actingAs($this->doctor);

    $response = $this->post('http://encounterh.medcore.local/encounters', [
        'patient_id' => $this->patient->id,
        'appointment_id' => $appointment->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'encounter_date' => now()->toDateString(),
    ]);

    $response->assertRedirect();

    $appointment->refresh();
    expect($appointment->status)->toBe('checked_in');
});

it('doctor can create a SOAP note on their encounter', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->doctor);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $response = $this->post(
        "http://encounterh.medcore.local/encounters/{$encounter->id}/clinical-notes",
        [
            'note_type' => 'soap',
            'subjective' => 'Patient reports headache',
            'objective' => 'BP 130/85, alert',
            'assessment' => 'Tension headache',
            'plan' => 'Prescribe ibuprofen',
        ]
    );

    $response->assertRedirect();

    $note = ClinicalNote::where('encounter_id', $encounter->id)->first();
    expect($note)->not->toBeNull();
    expect($note->author_id)->toBe($this->doctor->id);
    expect($note->subjective)->toBe('Patient reports headache');
});

it('subjective field is stored encrypted in the database', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->actingAs($this->doctor);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $this->post(
        "http://encounterh.medcore.local/encounters/{$encounter->id}/clinical-notes",
        ['note_type' => 'soap', 'subjective' => 'Severe chest pain']
    );

    $rawValue = DB::table('clinical_notes')
        ->where('encounter_id', $encounter->id)
        ->value('subjective');

    expect($rawValue)->not->toBe('Severe chest pain');
    expect($rawValue)->not->toBeNull();
});

it('receptionist cannot create a clinical note (403)', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->receptionist);

    $response = $this->post(
        "http://encounterh.medcore.local/encounters/{$encounter->id}/clinical-notes",
        ['note_type' => 'soap', 'subjective' => 'Unauthorized write']
    );

    $response->assertForbidden();
});

it('signed note cannot be edited', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $note = new ClinicalNote([
        'encounter_id' => $encounter->id,
        'author_id' => $this->doctor->id,
        'note_type' => 'soap',
        'is_signed' => true,
        'signed_at' => now(),
    ]);
    $note->subjective = 'Original signed note';
    $note->save();

    $this->actingAs($this->doctor);

    $response = $this->patch(
        "http://encounterh.medcore.local/encounters/{$encounter->id}/clinical-notes/{$note->id}",
        ['subjective' => 'Attempted edit']
    );

    $response->assertForbidden();
});

it('soft-deleted note is absent from normal queries but present in withTrashed', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $note = new ClinicalNote([
        'encounter_id' => $encounter->id,
        'author_id' => $this->doctor->id,
        'note_type' => 'soap',
        'is_signed' => false,
    ]);
    $note->subjective = 'To be deleted';
    $note->save();

    $this->actingAs($this->doctor);

    $this->delete(
        "http://encounterh.medcore.local/encounters/{$encounter->id}/clinical-notes/{$note->id}"
    );

    // Normal query excludes soft-deleted
    expect(ClinicalNote::where('id', $note->id)->count())->toBe(0);

    // withTrashed finds it
    expect(ClinicalNote::withTrashed()->where('id', $note->id)->count())->toBe(1);
});

it('logs phi_read:encounter_view after opening an encounter', function () {
    app(TenantManager::class)->setCurrent($this->tenant);

    $encounter = Encounter::create([
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->doctor->id,
        'encounter_type' => 'outpatient',
        'status' => 'open',
        'encounter_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->doctor);

    $response = $this->get("http://encounterh.medcore.local/encounters/{$encounter->id}");
    $response->assertOk();

    $log = AuditLog::where('auditable_type', Patient::class)
        ->where('auditable_id', $this->patient->id)
        ->where('action', 'phi_read:encounter_view')
        ->first();

    expect($log)->not->toBeNull();
});
