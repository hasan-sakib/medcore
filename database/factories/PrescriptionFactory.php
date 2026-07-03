<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'encounter_id' => null,
            'prescribed_by' => User::factory(),
            'status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
            'prescribed_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function filled(): static
    {
        return $this->state(['status' => 'filled']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
