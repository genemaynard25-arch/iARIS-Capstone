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

    // Deans: the Dean pages at /college, each for their own college. Named as in the mockups.
    // The college code goes in `department` (as in Account Management) until users.assigned_college exists.
    // updateOrCreate, so running the seeder again renames the old "Test Dean".
    foreach ([
        ['dean@gmail.com', 'Cherrie Villafuerte', 'CITE'],
        ['deancbeam@gmail.com', 'Jasmin De Silva', 'CBEAM'],
        ['deanceas@gmail.com', 'Gina Dimaano', 'CEAS'],
        ['deancihtm@gmail.com', 'Evangeline Necio', 'CIHTM'],
        ['deanccje@gmail.com', 'Aristotle Balba', 'CCJE'],
        ['deancon@gmail.com', 'Margie Ebreo', 'CON'],
    ] as [$email, $name, $college]) {
        \App\Models\User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'dean',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
            'department' => $college,
        ]);
    }

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
    // Named as in the mockups (and the Executive Office card, so the "You" tag shows). A VP's or VC's area
    // goes in `department` ("Vice President for Finance"). updateOrCreate, so running the seeder again fixes older names.
    foreach ([
        ['president@gmail.com', 'Edmundo L. Fernandez FSC', 'president', null],
        ['chancellor@gmail.com', 'Teodosia Merlin Suarez', 'chancellor', null],
        ['vp@gmail.com', 'Luz Magpantay', 'vice_president', 'Finance'],
        ['vpadmin@gmail.com', 'Cyrill Kino Escolano FSC', 'vice_president', 'Administration'],
        ['vpadv@gmail.com', 'Agustin Boquer FSC', 'vice_president', 'Advancement'],
        ['vc@gmail.com', 'Tessie Palma', 'vice_chancellor', 'Academics'],
        ['vcrii@gmail.com', 'Sheila Maloles', 'vice_chancellor', 'Research, Innovation & Internationalization'],
        ['vcmission@gmail.com', 'Wenifredo Angeles', 'vice_chancellor', 'Mission'],
    ] as [$email, $name, $role, $department]) {
        \App\Models\User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => $role,
            'access_type' => 'viewer',
            'department' => $department,
        ]);
    }

    // Integrated School Principal: the Dean pages by level, at /principal (Kindergarten to Senior High). Named as in the mockup.
    \App\Models\User::updateOrCreate(['email' => 'principal@gmail.com'], [
        'name' => 'Kristine Anne Dimaculangan',
        'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        'role' => 'principal',
        'access_type' => 'viewer',
        'assigned_level' => 'is',
    ]);

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