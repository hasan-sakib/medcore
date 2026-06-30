<?php

namespace Database\Factories;

use App\Models\ClinicalNote;
use App\Models\Encounter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalNote>
 */
class ClinicalNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'encounter_id' => Encounter::factory(),
            'author_id' => User::factory(),
            'note_type' => 'soap',
            'subjective' => $this->faker->paragraph(),
            'objective' => $this->faker->paragraph(),
            'assessment' => $this->faker->sentence(),
            'plan' => $this->faker->paragraph(),
            'body' => null,
            'is_signed' => false,
            'signed_at' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state([
            'is_signed' => true,
            'signed_at' => now(),
        ]);
    }

    public function progress(): static
    {
        return $this->state([
            'note_type' => 'progress',
            'subjective' => null,
            'objective' => null,
            'assessment' => null,
            'plan' => null,
            'body' => $this->faker->paragraph(),
        ]);
    }
}
