<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create multiple doctors with unique users
        $specializations = ['Cardiology', 'Dermatology', 'Neurology', 'Pediatrics', 'Oncology'];
        foreach ($specializations as $i => $specialization) {
            $user = User::factory()->create([
                'name' => $specialization . ' Doctor',
                'email' => strtolower($specialization) . '@example.com',
                'role' => 'doctor',
                'password' => Hash::make('password'),
            ]);
            Doctor::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'specialization' => $specialization,
                'availability' => json_encode([
                    'monday' => ['09:00-12:00', '14:00-17:00'],
                    'tuesday' => ['09:00-12:00'],
                ]),
            ]);
        }
    }
}
