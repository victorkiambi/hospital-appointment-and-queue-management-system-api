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
        $doctorNames = [
            ['name' => 'Dr. Alice Smith', 'email' => 'alice.smith@hospital.com'],
            ['name' => 'Dr. Bob Johnson', 'email' => 'bob.johnson@hospital.com'],
            ['name' => 'Dr. Carol Lee', 'email' => 'carol.lee@hospital.com'],
            ['name' => 'Dr. David Kim', 'email' => 'david.kim@hospital.com'],
            ['name' => 'Dr. Eva Brown', 'email' => 'eva.brown@hospital.com'],
        ];
        foreach ($doctorNames as $doc) {
            User::factory()->create([
                'name' => $doc['name'],
                'email' => $doc['email'],
                'role' => 'doctor',
                'password' => Hash::make('password'),
            ]);
        }

        // Patients
        $patientNames = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com'],
            ['name' => 'Michael Green', 'email' => 'michael.green@example.com'],
            ['name' => 'Emily White', 'email' => 'emily.white@example.com'],
            ['name' => 'Chris Black', 'email' => 'chris.black@example.com'],
        ];
        foreach ($patientNames as $pat) {
            User::factory()->create([
                'name' => $pat['name'],
                'email' => $pat['email'],
                'role' => 'patient',
                'password' => Hash::make('password'),
            ]);
        }
    }
}