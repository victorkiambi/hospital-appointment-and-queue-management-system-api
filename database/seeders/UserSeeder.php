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
        // Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        // Doctors
        for ($i = 1; $i <= 5; $i++) {
            User::factory()->create([
                'name' => "Doctor $i",
                'email' => "doctor$i@example.com",
                'role' => 'doctor',
                'password' => Hash::make('password'),
            ]);
        }

        // Patients
        for ($i = 1; $i <= 5; $i++) {
            User::factory()->create([
                'name' => "Patient $i",
                'email' => "patient$i@example.com",
                'role' => 'patient',
                'password' => Hash::make('password'),
            ]);
        }
    }
}