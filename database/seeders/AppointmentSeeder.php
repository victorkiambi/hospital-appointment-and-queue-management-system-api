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
        $doctors = Doctor::all();
        $patients = Patient::all();
        $patientCount = $patients->count();
        $appointmentTime = now()->addDay();
        foreach ($doctors as $dIndex => $doctor) {
            // Assign two appointments per doctor with different patients
            for ($i = 0; $i < 2; $i++) {
                $patient = $patients[($dIndex * 2 + $i) % $patientCount];
                Appointment::updateOrCreate([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'scheduled_at' => $appointmentTime->copy()->addDays($dIndex * 2 + $i)->format('Y-m-d H:i:s'),
                ], [
                    'status' => 'scheduled',
                ]);
            }
        }
    }
}
