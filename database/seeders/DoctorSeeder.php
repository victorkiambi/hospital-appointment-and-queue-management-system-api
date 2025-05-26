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
        $specializations = ['Cardiology', 'Dermatology', 'Neurology', 'Pediatrics', 'Oncology'];
        $doctorUsers = User::where('role', 'doctor')->orderBy('id')->get();
        foreach ($doctorUsers as $i => $user) {
            Doctor::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'specialization' => $specializations[$i % count($specializations)],
                'availability' => json_encode([
                    [ 'day' => 'Monday', 'start' => '09:00', 'end' => '12:00' ],
                    [ 'day' => 'Monday', 'start' => '14:00', 'end' => '17:00' ],
                    [ 'day' => 'Tuesday', 'start' => '09:00', 'end' => '12:00' ],
                ]),
            ]);
        }
    }
}
