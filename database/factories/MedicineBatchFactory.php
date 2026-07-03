<?php

namespace Database\Factories;

use App\Models\Medicine;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineBatchFactory extends Factory
{
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(50, 500);

        return [
            'tenant_id' => Tenant::factory(),
            'medicine_id' => Medicine::factory(),
            'supplier_id' => null,
            'purchase_order_id' => null,
            'batch_number' => 'BATCH-'.$this->faker->unique()->numerify('#########'),
            'lot_number' => $this->faker->optional()->numerify('LOT-######'),
            'quantity_received' => $qty,
            'quantity_on_hand' => $qty,
            'unit_cost' => $this->faker->randomFloat(2, 0.5, 50),
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'manufactured_date' => now()->subMonths(6)->toDateString(),
            'received_by' => null,
            'status' => 'active',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function expiringSoon(): static
    {
        return $this->state(['expiry_date' => now()->addDays(7)->toDateString()]);
    }

    public function expired(): static
    {
        return $this->state([
            'expiry_date' => now()->subDay()->toDateString(),
            'status' => 'expired',
        ]);
    }

    public function depleted(): static
    {
        return $this->state([
            'quantity_on_hand' => 0,
            'status' => 'depleted',
        ]);
    }
}
