<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'appointment_id' => null,
            'attending_doctor_id' => User::factory(),
            'department_id' => null,
            'encounter_type' => 'outpatient',
            'status' => 'open',
            'chief_complaint' => $this->faker->sentence(),
            'encounter_date' => now()->toDateString(),
            'admitted_at' => null,
            'discharged_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'discharged_at' => now(),
        ]);
    }

    public function inpatient(): static
    {
        return $this->state([
            'encounter_type' => 'inpatient',
            'admitted_at' => now(),
        ]);
    }
}
