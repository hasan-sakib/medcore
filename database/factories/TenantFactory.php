<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'domain' => null,
            'status' => 'active',
            'subscription_plan' => 'professional',
            'settings' => null,
            'trial_ends_at' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }

    public function trial(): static
    {
        return $this->state([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(30),
        ]);
    }
}
