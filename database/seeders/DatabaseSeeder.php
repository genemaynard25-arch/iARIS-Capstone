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
        ['email' => 'admin@iaris.local'],
        [
            'name' => 'Test Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'admin',
            'access_type' => 'editor',
        ]
    );

    \App\Models\User::firstOrCreate(
        ['email' => 'dean@iaris.local'],
        [
            'name' => 'Test Dean',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'dean',
            'access_type' => 'viewer',
            'assigned_level' => 'college',
        ]
    );

    \App\Models\User::firstOrCreate(
        ['email' => 'staff@iaris.local'],
        [
            'name' => 'Test IATO Staff',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'iato_staff',
            'access_type' => 'editor',
        ]
    );
    }
}