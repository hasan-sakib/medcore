<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vital>
 */
class VitalFactory extends Factory
{
    public function definition(): array
    {
        $weightKg = $this->faker->randomFloat(1, 45, 120);
        $heightCm = $this->faker->randomFloat(1, 150, 195);
        $heightM = $heightCm / 100;
        $bmi = round($weightKg / ($heightM ** 2), 1);

        return [
            'tenant_id' => Tenant::factory(),
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'temperature_c' => $this->faker->randomFloat(1, 36.0, 38.5),
            'pulse_bpm' => $this->faker->numberBetween(60, 100),
            'bp_systolic' => $this->faker->numberBetween(100, 140),
            'bp_diastolic' => $this->faker->numberBetween(60, 90),
            'spo2_pct' => $this->faker->randomFloat(1, 95.0, 100.0),
            'respiratory_rate' => $this->faker->numberBetween(12, 20),
            'weight_kg' => $weightKg,
            'height_cm' => $heightCm,
            'bmi' => $bmi,
            'glucose_mmol' => $this->faker->randomFloat(2, 4.0, 7.0),
            'pain_scale' => $this->faker->numberBetween(0, 3),
            'notes' => null,
        ];
    }
}
