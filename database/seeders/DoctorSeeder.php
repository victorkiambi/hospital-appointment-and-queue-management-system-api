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
        $availabilities = [
            // Alice
            [
                [ 'day' => 'Monday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Monday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Friday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Friday', 'start' => '14:00', 'end' => '17:00' ],
            ],
            // Bob
            [
                [ 'day' => 'Tuesday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Tuesday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Thursday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Thursday', 'start' => '14:00', 'end' => '17:00' ],
            ],
            // Carol
            [
                [ 'day' => 'Wednesday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Wednesday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Thursday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Thursday', 'start' => '14:00', 'end' => '17:00' ],
            ],
            // David
            [
                [ 'day' => 'Monday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Monday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Wednesday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Wednesday', 'start' => '14:00', 'end' => '17:00' ],
            ],
            // Eva
            [
                [ 'day' => 'Friday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Friday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Saturday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Saturday', 'start' => '14:00', 'end' => '17:00' ],
            ],
        ];
        foreach ($doctorUsers as $i => $user) {
            Doctor::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'specialization' => $specializations[$i % count($specializations)],
                'availability' => json_encode($availabilities[$i % count($availabilities)]),
            ]);
        }
    }
}
