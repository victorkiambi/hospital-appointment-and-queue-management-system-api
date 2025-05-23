<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific users for each role
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
        User::factory()->create([
            'name' => 'Doctor User',
            'email' => 'doctor@example.com',
            'role' => 'doctor',
            'password' => Hash::make('password'),
        ]);
        User::factory()->create([
            'name' => 'Patient User',
            'email' => 'patient@example.com',
            'role' => 'patient',
            'password' => Hash::make('password'),
        ]);
        // Create additional random users
        User::factory(7)->create();
    }
}