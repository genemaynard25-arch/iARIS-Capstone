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

    // President / Chancellor office: read-only dashboard at /chancellor.
    // Named after the Chancellor in the Executive Office card, so the "You" tag shows.
    \App\Models\User::firstOrCreate(
        ['email' => 'chancellor@gmail.com'],
        [
            'name' => 'Merlin Teodosia C. Suarez',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'chancellor',
            'access_type' => 'viewer',
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