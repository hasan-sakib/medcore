<?php

use App\Models\Appointment;
use App\Models\ClinicalNote;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\Encounter;
use App\Models\EncounterDiagnosis;
use App\Models\Patient;
use App\Models\Vital;
use App\Traits\BelongsToTenant;

/**
 * Architecture tests — enforce structural invariants across the codebase.
 *
 * These run via Pest's arch() helper. They assert design constraints so that
 * an engineer adding a new tenant-scoped model cannot accidentally omit the
 * required trait.
 *
 * Add new tenant-scoped model classes to the $tenantModels array below as
 * phases are implemented.
 */

// Phase 1 (Foundation) + Phase 2 (EMR)
$tenantModels = [
    // Phase 2: EMR & Patient Lifecycle
    Department::class,
    DoctorSchedule::class,
    Patient::class,
    Appointment::class,
    Encounter::class,
    ClinicalNote::class,
    Vital::class,
    EncounterDiagnosis::class,
    // Diagnosis::class is intentionally excluded — global ICD-10 reference, no tenant_id
];

foreach ($tenantModels as $modelClass) {
    it("$modelClass uses BelongsToTenant trait", function () use ($modelClass) {
        expect($modelClass)
            ->toUse(BelongsToTenant::class);
    });
}

// Global architecture rules
arch('app code does not use dd or dump')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

arch('controllers are not invokable on resource routes')
    ->expect('App\Http\Controllers')
    ->not->toHaveMethod('handle');

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');
