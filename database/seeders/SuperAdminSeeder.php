<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admins are identified by tenant_id = null.
        // They bypass spatie/laravel-permission entirely (teams mode requires
        // a non-null team key in the composite primary key — null is invalid in MySQL PKs).
        // Access control is enforced by EnsureSuperAdmin middleware via User::isSuperAdmin().

        $superAdmin = User::firstOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@medcore.local')],
            [
                'name'      => 'Platform Administrator',
                'password'  => bcrypt(env('SUPER_ADMIN_PASSWORD', 'changeme-in-production!')),
                'tenant_id' => null,
            ]
        );

        $this->command->info('Super admin provisioned: '.$superAdmin->email);
    }
}
