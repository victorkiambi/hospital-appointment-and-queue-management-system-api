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
        $patientUsers = User::where('role', 'patient')->orderBy('id')->get();
        foreach ($patientUsers as $i => $user) {
            Patient::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'medical_record_number' => 'MRN-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
