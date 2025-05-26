<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Queue;
use Carbon\Carbon;

class QueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = Doctor::orderBy('id')->get();
        foreach ($doctors as $doctor) {
            $appointments = Appointment::where('doctor_id', $doctor->id)->orderBy('scheduled_at')->get();
            $position = 1;
            foreach ($appointments as $appointment) {
                Queue::updateOrCreate([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $appointment->patient_id,
                    'status' => 'waiting',
                ], [
                    'position' => $position++,
                    'called_at' => null,
                ]);
            }
        }
    }
}
