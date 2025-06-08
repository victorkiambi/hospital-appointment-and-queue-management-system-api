<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateAs($role = 'patient')
    {
        $user = User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $response->json('token');
        return [$user, $token];
    }

    public function test_doctor_cannot_be_double_booked()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create();
        $slot = now()->addDay()->setTime(10, 0);
        // First booking should succeed
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response1->assertStatus(201);
        // Second booking for same doctor and time should fail
        $otherPatient = Patient::factory()->create();
        $otherUser = $otherPatient->user;
        $otherToken = $otherUser->createToken('auth_token')->plainTextToken;
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response2->assertStatus(409)
            ->assertJsonPath('message', 'Double-booking detected for doctor at this time.');
    }

    public function test_patient_cannot_book_with_nonexistent_doctor()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $invalidDoctorId = 999999;
        $slot = now()->addDay()->setTime(11, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $invalidDoctorId,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response->assertStatus(422)
            ->assertJsonPath('message', 'The selected doctor id is invalid.');
    }

    public function test_patient_cannot_book_with_unavailable_doctor()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        // Doctor only available on Monday 09:00-12:00
        $doctor = Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => 'Monday', 'start' => '09:00', 'end' => '12:00'],
            ]),
        ]);
        // Try to book on Tuesday
        $slot = now()->next('Tuesday')->setTime(10, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response->assertStatus(409)
            ->assertJsonPath('message', 'Doctor is not available at the selected time.');
    }

    public function test_patient_can_cancel_own_appointment()
    {
        // Create patient and appointment
        [$user, $token] = $this->authenticateAs('patient');
        $patient = \App\Models\Patient::factory()->create(['user_id' => $user->id]);
        $doctor = \App\Models\Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => now()->format('l'), 'start' => '09:00', 'end' => '18:00'],
            ]),
        ]);
        $slot = now()->addDay()->setTime(14, 0);
        $appointment = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ]);
        // Add a queue entry for this appointment
        $queue = \App\Models\Queue::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'position' => 1,
            'status' => 'waiting',
        ]);
        // Patient cancels their own appointment
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/appointments/' . $appointment->id);
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Appointment deleted successfully');
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    public function test_patient_cannot_cancel_others_appointment()
    {
        // Create two patients and an appointment for the second
        $patient1 = \App\Models\Patient::factory()->create();
        $user1 = $patient1->user;
        $token1 = $this->postJson('/api/v1/login', [
            'email' => $user1->email,
            'password' => 'password',
        ])->json('token');
        $patient2 = \App\Models\Patient::factory()->create();
        $doctor = \App\Models\Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => now()->format('l'), 'start' => '09:00', 'end' => '18:00'],
            ]),
        ]);
        $slot = now()->addDay()->setTime(15, 0);
        $appointment = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
            'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ]);
        // Patient1 tries to cancel Patient2's appointment
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->deleteJson('/api/v1/appointments/' . $appointment->id);
        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_same_day_appointment_adds_patient_to_queue()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = \App\Models\Patient::factory()->create(['user_id' => $user->id]);
        $doctor = \App\Models\Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => now()->format('l'), 'start' => '09:00', 'end' => '18:00'],
            ]),
        ]);
        $slot = now()->setTime(10, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('queues', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'waiting',
        ]);
    }

    public function test_future_appointment_does_not_add_patient_to_queue()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = \App\Models\Patient::factory()->create(['user_id' => $user->id]);
        $doctor = \App\Models\Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => now()->addDay()->format('l'), 'start' => '09:00', 'end' => '18:00'],
            ]),
        ]);
        $slot = now()->addDay()->setTime(10, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot->format('Y-m-d H:i:s'),
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseMissing('queues', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'waiting',
        ]);
    }

    public function test_no_duplicate_queue_entries_for_same_day_appointments()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = \App\Models\Patient::factory()->create(['user_id' => $user->id]);
        $doctor = \App\Models\Doctor::factory()->create([
            'availability' => json_encode([
                ['day' => now()->format('l'), 'start' => '09:00', 'end' => '18:00'],
            ]),
        ]);
        $slot1 = now()->setTime(10, 0);
        $slot2 = now()->setTime(11, 0);
        // Book first appointment
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot1->format('Y-m-d H:i:s'),
            ]);
        // Book second appointment (same day, same doctor)
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $slot2->format('Y-m-d H:i:s'),
            ]);
        $this->assertEquals(1, \App\Models\Queue::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'waiting')
            ->count());
    }

}