<?php

namespace Database\Seeders;

use App\Services\TenantProvisioningService;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    public function __construct(private TenantProvisioningService $provisioner) {}

    public function run(): void
    {
        // Demo Tenant A — City General Hospital
        $tenantA = $this->provisioner->provision(
            tenantData: [
                'name' => 'City General Hospital',
                'slug' => 'citygeneral',
                'plan' => 'professional',
            ],
            adminData: [
                'name'     => 'CG Admin',
                'email'    => 'admin@citygeneral.medcore.local',
                'password' => 'demo-password-123!',
            ]
        );

        // Demo Tenant B — Sunrise Clinic (isolation test subject)
        $tenantB = $this->provisioner->provision(
            tenantData: [
                'name' => 'Sunrise Clinic',
                'slug' => 'sunrise',
                'plan' => 'basic',
            ],
            adminData: [
                'name'     => 'Sunrise Admin',
                'email'    => 'admin@sunrise.medcore.local',
                'password' => 'demo-password-456!',
            ]
        );

        $this->command->info("Demo tenants seeded: {$tenantA->slug}, {$tenantB->slug}");
    }
}
