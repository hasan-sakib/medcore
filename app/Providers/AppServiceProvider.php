<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ClinicalNote;
use App\Models\Encounter;
use App\Models\Patient;
use App\Policies\AppointmentPolicy;
use App\Policies\ClinicalNotePolicy;
use App\Policies\EncounterPolicy;
use App\Policies\PatientPolicy;
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

        // Strict mode in non-production to surface N+1, lazy-loading, mass-assignment issues early
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prevent destructive commands in production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Vite::prefetch(concurrency: 3);
    }
}
