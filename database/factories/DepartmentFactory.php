<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    private static array $names = [
        'Cardiology', 'Emergency', 'Pediatrics', 'Orthopedics',
        'Radiology', 'Oncology', 'Neurology', 'Obstetrics',
        'General Surgery', 'Internal Medicine',
    ];

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(self::$names);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'code' => strtoupper(Str::substr($name, 0, 5).$this->faker->numerify('##')),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
