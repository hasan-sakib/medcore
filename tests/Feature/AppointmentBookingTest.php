<?php

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Carbon\Carbon;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Booking Hospital', 'slug' => 'bookingh', 'plan' => 'professional'],
        ['name' => 'BH Admin', 'email' => 'admin@bookingh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->department = Department::create(['name' => 'General', 'code' => 'GEN']);

    $this->doctor = User::factory()->forTenant($this->tenant)->create();
    $this->doctor->assignRole('doctor');

    $this->receptionist = User::factory()->forTenant($this->tenant)->create();
    $this->receptionist->assignRole('receptionist');

    // Create a Monday schedule (day_of_week = 1)
    $this->schedule = DoctorSchedule::create([
        'user_id' => $this->doctor->id,
        'department_id' => $this->department->id,
        'day_of_week' => 1, // Monday
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'slot_duration' => 15,
        'max_patients' => 20,
        'is_active' => true,
    ]);

    $this->actingAs($this->receptionist);

    $patientService = app(PatientService::class);
    $this->patient = $patientService->create([
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'date_of_birth' => '1990-01-01',
    ]);
});

it('books an appointment and sets correct ends_at', function () {
    $service = app(AppointmentService::class);

    // Find next Monday
    $monday = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0, 0);

    $appointment = $service->book([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => $monday,
    ]);

    expect($appointment->status)->toBe('pending');
    expect($appointment->doctor_id)->toBe($this->doctor->id);
    expect($appointment->tenant_id)->toBe($this->tenant->id);

    // ends_at should be scheduled_at + 15 minutes (slot_duration)
    expect($appointment->scheduled_at->diffInMinutes($appointment->ends_at, true))->toEqual(15);
});

it('throws RuntimeException when slot has no doctor schedule', function () {
    $service = app(AppointmentService::class);

    // Sunday (day 0) — no schedule exists
    $sunday = Carbon::now()->next(Carbon::SUNDAY)->setTime(10, 0, 0);

    expect(fn () => $service->book([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => $sunday,
    ]))->toThrow(RuntimeException::class, 'Doctor has no schedule on this day.');
});

it('throws RuntimeException when slot conflicts with existing appointment', function () {
    $service = app(AppointmentService::class);

    $monday = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0, 0);

    // Book first appointment
    $service->book([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => $monday,
    ]);

    // Attempt to book the same slot
    expect(fn () => $service->book([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => $monday,
    ]))->toThrow(RuntimeException::class, 'This slot is no longer available.');
});

it('returns empty array of slots for a day with no schedule', function () {
    $service = app(AppointmentService::class);

    $sunday = Carbon::now()->next(Carbon::SUNDAY);
    $slots = $service->availableSlots($this->doctor->id, $sunday);

    expect($slots)->toBeEmpty();
});

it('returns available slots for a scheduled day', function () {
    $service = app(AppointmentService::class);

    $monday = Carbon::now()->next(Carbon::MONDAY);
    $slots = $service->availableSlots($this->doctor->id, $monday);

    // 8:00–17:00 in 15-min slots = 36 slots
    expect(count($slots))->toBeGreaterThan(0);

    $firstSlot = $slots[0];
    expect($firstSlot['start'])->toBeInstanceOf(Carbon::class);
    expect($firstSlot['start']->diffInMinutes($firstSlot['end'], true))->toEqual(15);
});

it('doctor cannot view another doctor\'s appointment', function () {
    $service = app(AppointmentService::class);

    $monday = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0, 0);
    $appointment = $service->book([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'scheduled_at' => $monday,
    ]);

    $otherDoctor = User::factory()->forTenant($this->tenant)->create();
    $otherDoctor->assignRole('doctor');

    $this->actingAs($otherDoctor);

    $response = $this->get("http://bookingh.medcore.local/appointments/{$appointment->id}");
    $response->assertForbidden();
});
