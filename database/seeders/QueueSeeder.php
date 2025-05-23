<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use Carbon\Carbon;

class QueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = Doctor::all();
        $patients = Patient::all();
        $patientCount = $patients->count();
        foreach ($doctors as $dIndex => $doctor) {
            // Assign two patients per doctor in the queue
            for ($i = 0; $i < 2; $i++) {
                $patient = $patients[($dIndex * 2 + $i) % $patientCount];
                Queue::updateOrCreate([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'status' => 'waiting',
                ], [
                    'position' => $i + 1,
                    'called_at' => null,
                ]);
            }
        }
    }
}
