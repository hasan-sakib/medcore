<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ClinicalNote;
use App\Models\DispenseRecord;
use App\Models\Encounter;
use App\Models\Medicine;
use App\Models\OrSchedule;
use App\Models\Patient;
use App\Models\Prescription;
use App\Policies\AppointmentPolicy;
use App\Policies\ClinicalNotePolicy;
use App\Policies\DispenseRecordPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\MedicinePolicy;
use App\Policies\OperatingRoomPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        // Phase 2 policy registration
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Encounter::class, EncounterPolicy::class);
        Gate::policy(ClinicalNote::class, ClinicalNotePolicy::class);

        // Phase 3 policy registration
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(DispenseRecord::class, DispenseRecordPolicy::class);

        // Phase 4: OrSchedule has no dedicated policy class, so auto-discovery finds nothing
        // and every OR-schedule action is denied. OperatingRoomPolicy covers it.
        Gate::policy(OrSchedule::class, OperatingRoomPolicy::class);

        // Strict mode in non-production to surface N+1, lazy-loading, mass-assignment issues early
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prevent destructive commands in production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Vite::prefetch(concurrency: 3);
    }
}
