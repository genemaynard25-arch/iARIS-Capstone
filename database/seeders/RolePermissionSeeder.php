<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        RolePermission::truncate();

        $roles = [
            'iato_staff', 'program_chair', 'dean', 'principal', 'registrar',
            'lamp', 'president', 'vice_president', 'chancellor', 'vice_chancellor', 'toic', 'director',
        ];

        // module key => roles that have it on
        $modules = [
            // TOIC: only the dashboard (it sends them to /toic); no applicant modules
            'dashboard' => ['iato_staff', 'program_chair', 'dean', 'principal', 'registrar', 'lamp', 'president', 'vice_president', 'chancellor', 'vice_chancellor', 'toic', 'director'],
            'applicants' => ['iato_staff', 'program_chair', 'dean', 'principal', 'registrar'],
            'reports' => ['iato_staff', 'program_chair', 'dean', 'principal', 'registrar', 'lamp', 'president', 'vice_president', 'chancellor', 'vice_chancellor'],
            'analytics' => ['iato_staff', 'program_chair', 'dean', 'principal', 'president', 'vice_president', 'chancellor', 'vice_chancellor'],
            'college' => ['iato_staff', 'program_chair', 'dean', 'registrar'],
            'is' => ['iato_staff', 'principal', 'registrar'],
            'scholars' => ['iato_staff', 'lamp'],
            'import' => ['iato_staff'],
            'edit' => ['iato_staff'],
            'audit' => ['iato_staff'],
        ];

        foreach ($modules as $moduleKey => $allowedRoles) {
            foreach ($roles as $role) {
                RolePermission::updateOrCreate(
                    ['role' => $role, 'module' => $moduleKey],
                    ['enabled' => in_array($role, $allowedRoles)]
                );
            }
        }
    }
}