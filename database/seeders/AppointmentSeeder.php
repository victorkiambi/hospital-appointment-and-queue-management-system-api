<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = Doctor::orderBy('id')->get();
        $patients = Patient::orderBy('id')->get();
        $baseTime = Carbon::now()->addDays(1)->setTime(9, 0, 0);
        foreach ($patients as $pIndex => $patient) {
            foreach ($doctors as $dIndex => $doctor) {
                $scheduledAt = $baseTime->copy()->addDays($pIndex)->addHours($dIndex * 2);
                Appointment::updateOrCreate([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                ], [
                    'status' => 'scheduled',
                ]);
            }
        }
    }
}
