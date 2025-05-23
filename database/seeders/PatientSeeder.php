<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create multiple patients with unique users
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create([
                'name' => 'Patient ' . $i,
                'email' => 'patient' . $i . '@example.com',
                'role' => 'patient',
                'password' => bcrypt('password'),
            ]);
            Patient::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'medical_record_number' => 'MRN-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
