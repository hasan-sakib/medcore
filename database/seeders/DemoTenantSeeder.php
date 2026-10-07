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
        $tenantA->update([
            'tagline' => 'Compassionate care for every patient, every day.',
            'description' => 'City General Hospital has served the community for over 50 years, offering a full spectrum of clinical services from emergency care to advanced surgical procedures. Our multidisciplinary team is committed to delivering patient-centered care.',
            'address' => '100 Health Plaza, Downtown',
            'city' => 'Cityville',
            'phone' => '+1 (555) 100-2000',
            'email' => 'info@citygeneral.example.com',
            'features' => ['Emergency', 'ICU', 'Pharmacy', 'Laboratory', 'Surgery', 'Cardiology', 'Pediatrics'],
            'is_publicly_listed' => true,
        ]);

        // Demo Tenant B — Sunrise Clinic
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
        $tenantB->update([
            'tagline' => 'Specialized outpatient care close to home.',
            'description' => 'Sunrise Clinic provides outpatient specialist consultations, diagnostics, and preventive health services. We focus on fast, convenient care without sacrificing quality.',
            'address' => '45 Sunrise Avenue, Westside',
            'city' => 'Westfield',
            'phone' => '+1 (555) 200-4000',
            'email' => 'hello@sunrise.example.com',
            'features' => ['General Medicine', 'Diagnostics', 'Pharmacy', 'Radiology'],
            'is_publicly_listed' => true,
        ]);

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

        $specialties = ['General Medicine', 'Cardiology', 'Pediatrics', 'Emergency Medicine', 'Internal Medicine'];
        $bios = [
            'Board-certified with over 15 years of clinical experience. Dedicated to evidence-based patient care.',
            'Passionate about preventive medicine and chronic disease management. Fluent in 3 languages.',
            'Fellowship-trained specialist with a focus on minimally invasive techniques.',
            'Experienced clinician committed to compassionate, patient-centered care.',
        ];

        foreach ($doctors as $i => $doctor) {
            $doctor->update([
                'specialty' => $specialties[$i % count($specialties)],
                'bio' => $bios[$i % count($bios)],
                'is_publicly_listed' => true,
            ]);
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
