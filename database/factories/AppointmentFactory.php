<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $scheduledAt = now()->addDays(rand(1, 14))->setMinutes(0)->setSeconds(0);

        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'department_id' => null,
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes(15),
            'status' => 'pending',
            'reason' => $this->faker->sentence(),
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
