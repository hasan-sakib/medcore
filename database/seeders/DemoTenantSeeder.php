<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
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
                'name' => 'CG Admin',
                'email' => 'admin@citygeneral.medcore.local',
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
                'name' => 'Sunrise Admin',
                'email' => 'admin@sunrise.medcore.local',
                'password' => 'demo-password-456!',
            ]
        );

        $this->seedDepartmentsAndSchedules($tenantA);
        $this->seedDepartmentsAndSchedules($tenantB);

        $this->command->info("Demo tenants seeded: {$tenantA->slug}, {$tenantB->slug}");
    }

    private function seedDepartmentsAndSchedules(Tenant $tenant): void
    {
        $manager = app(TenantManager::class);
        $manager->setCurrent($tenant);

        $departments = [
            ['name' => 'Emergency',       'code' => 'ER'],
            ['name' => 'General Medicine', 'code' => 'GM'],
            ['name' => 'Cardiology',       'code' => 'CARD'],
            ['name' => 'Pediatrics',       'code' => 'PED'],
        ];

        foreach ($departments as $dept) {
            Department::create([
                'name' => $dept['name'],
                'code' => $dept['code'],
                'is_active' => true,
            ]);
        }

        // Get the tenant's doctor users (role: doctor) and create weekly schedules
        $doctors = User::role('doctor')->get();

        if ($doctors->isEmpty()) {
            return;
        }

        $gmDept = Department::where('code', 'GM')->first();

        foreach ($doctors as $doctor) {
            // Monday–Friday schedule
            foreach (range(1, 5) as $dayOfWeek) {
                DoctorSchedule::create([
                    'user_id' => $doctor->id,
                    'department_id' => $gmDept->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'slot_duration' => 15,
                    'max_patients' => 24,
                    'is_active' => true,
                ]);
            }
        }

        $manager->clearCurrent();
    }
}
