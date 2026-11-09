<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Only the accounts the mock-defense demo flow actually uses, on top of admin@gmail.com.
// Run with: php artisan db:seed --class=DemoSeeder
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Step 3: Import Data + Applicants/Records, as a real editor rather than the admin
        User::updateOrCreate(['email' => 'staff@gmail.com'], [
            'name' => 'Test IATO Staff',
            'password' => Hash::make('password123'),
            'role' => 'iato_staff',
            'access_type' => 'editor',
            'status' => 'Active',
        ]);

        // Step 9: role-scoped view (CITE Dean only sees CITE applicants)
        User::updateOrCreate(['email' => 'dean@gmail.com'], [
            'name' => 'Cherrie Villafuerte',
            'password' => Hash::make('password123'),
            'role' => 'dean',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
            'assigned_college' => 'CITE',
            'department' => 'CITE',
            'status' => 'Active',
        ]);

        // Optional: Scholars / LAMP cross-check beat
        User::updateOrCreate(['email' => 'lamp@gmail.com'], [
            'name' => 'Test LAMP Office',
            'password' => Hash::make('password123'),
            'role' => 'lamp',
            'access_type' => 'viewer',
            'status' => 'Active',
        ]);

        // Optional: Registrar view
        User::updateOrCreate(['email' => 'registrar@gmail.com'], [
            'name' => 'Remian Santos',
            'password' => Hash::make('password123'),
            'role' => 'registrar',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
            'status' => 'Active',
        ]);

        // Module access for these roles (dashboard/applicants/etc.), so none of them 403 on login.
        $this->call(RolePermissionSeeder::class);
    }
}
