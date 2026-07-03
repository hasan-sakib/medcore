<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineFactory extends Factory
{
    public function definition(): array
    {
        $unitTypes = ['tablet', 'capsule', 'ml', 'unit', 'vial'];

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'generic_name' => $this->faker->words(2, true),
            'sku' => 'MED-'.$this->faker->unique()->numerify('######'),
            'category' => $this->faker->randomElement(['antibiotic', 'analgesic', 'antidiabetic', 'antihypertensive', 'supplement']),
            'unit_type' => $this->faker->randomElement($unitTypes),
            'strength' => $this->faker->numerify('###mg'),
            'min_stock_level' => 5,
            'reorder_level' => 10,
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
