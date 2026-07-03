<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'supplier_id' => null,
            'po_number' => 'PO-'.strtoupper(Str::random(8)),
            'status' => 'draft',
            'notes' => null,
            'ordered_at' => null,
            'expected_delivery_date' => now()->addDays(14)->toDateString(),
            'received_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent', 'ordered_at' => now()]);
    }

    public function received(): static
    {
        return $this->state(['status' => 'received', 'ordered_at' => now()->subDay(), 'received_at' => now()]);
    }
}
