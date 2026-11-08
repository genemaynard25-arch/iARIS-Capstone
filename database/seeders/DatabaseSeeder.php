<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    \App\Models\User::firstOrCreate(
        ['email' => 'admin@gmail.com'],
        [
            'name' => 'Test Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'admin',
            'access_type' => 'editor',
        ]
    );

    \App\Models\User::firstOrCreate(
        ['email' => 'dean@gmail.com'],
        [
            'name' => 'Test Dean',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'dean',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
        ]
    );

    \App\Models\User::firstOrCreate(
        ['email' => 'staff@gmail.com'],
        [
            'name' => 'Test IATO Staff',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'iato_staff',
            'access_type' => 'editor',
        ]
    );

     \App\Models\User::firstOrCreate(
        ['email' => 'raphael_lorenzo_go@dlsl.edu.ph'],
        [
            'name' => 'Raphael Go',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'admin',
            'access_type' => 'editor',
        ]
    );

    \App\Models\User::firstOrCreate(
        ['email' => 'shsprincipal@gmail.com'],
        [
            'name' => 'Test SHS Principal',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'admin',
            'access_type' => 'viewer',
            'assigned_level' => 'is',
            'assigned_sub_level' => 'shs',
        ]
        );

    // Executive Office (President, Chancellor, Vice Presidents, Vice Chancellors): read-only pages at /executive.
    // Named as in the mockups (and the Executive Office card, so the "You" tag shows). The Vice President
    // has no name in the mockups yet. updateOrCreate, so running the seeder again fixes older names.
    foreach ([
        ['president@gmail.com', 'Edmundo L. Fernandez FSC', 'president'],
        ['chancellor@gmail.com', 'Teodosia Merlin Suarez', 'chancellor'],
        ['vp@gmail.com', 'Paolo Mendoza', 'vice_president'],
        ['vc@gmail.com', 'Tessie Palma', 'vice_chancellor'],
    ] as [$email, $name, $role]) {
        \App\Models\User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => $role,
            'access_type' => 'viewer',
        ]);
    }

    // Program Chair: the College dashboard at /college, locked to one program (BS Computer Science for now)
    \App\Models\User::firstOrCreate(
        ['email' => 'chair@gmail.com'],
        [
            'name' => 'Antonio L. Mercado',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'program_chair',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
        ]
    );

    // College Registrar: Registrar pages at /registrar (records are view only)
    \App\Models\User::firstOrCreate(
        ['email' => 'registrar@gmail.com'],
        [
            'name' => 'Remian Santos',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'registrar',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
        ]
    );

    // IS Registrar: the same Registrar pages, for Integrated School records only
    \App\Models\User::firstOrCreate(
        ['email' => 'isregistrar@gmail.com'],
        [
            'name' => 'Michelle Reyes',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'registrar',
            'access_type' => 'viewer',
            'assigned_level' => 'is',
        ]
    );

    // LAMP Office: scholar cross-check pages at /lamp
    \App\Models\User::firstOrCreate(
        ['email' => 'lamp@gmail.com'],
        [
            'name' => 'Test LAMP Office',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'lamp',
            'access_type' => 'viewer',
        ]
    );
    }
}