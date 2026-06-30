<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
        ]);

        // In local/testing environments, seed demo tenants
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoTenantSeeder::class,
            ]);
        }
    }
}
