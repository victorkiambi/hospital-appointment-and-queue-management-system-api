<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateAs($role = 'doctor')
    {
        $user = \App\Models\User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $response->json('token');
        return [$user, $token];
    }

    public function test_admin_can_create_doctor()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'doctor']);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/doctors', [
                'user_id' => $user->id,
                'specialization' => 'Cardiology',
                'availability' => [
                    ['day' => 'Monday', 'start' => '09:00', 'end' => '12:00'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'user', 'specialization', 'availability', 'created_at', 'updated_at'
                ],
                'message',
                'errors'
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.specialization', 'Cardiology');
    }

    public function test_non_admin_cannot_create_doctor()
    {
        $user = User::factory()->create(['role' => 'doctor']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/doctors', [
                'user_id' => $user->id,
                'specialization' => 'Cardiology',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_doctor()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        $response = $this->actingAs($admin)
            ->putJson("/api/v1/doctors/{$doctor->id}", [
                'specialization' => 'Neurology',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.specialization', 'Neurology');
    }

    public function test_doctor_can_update_own_profile()
    {
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $user = $doctor->user;

        $response = $this->actingAs($user)
            ->putJson("/api/v1/doctors/{$doctor->id}", [
                'specialization' => 'Dermatology',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.specialization', 'Dermatology');
    }

    public function test_non_owner_doctor_cannot_update_other_doctor()
    {
        $doctor = Doctor::factory()->create();
        $otherDoctor = Doctor::factory()->create();
        $user = $otherDoctor->user;

        $response = $this->actingAs($user)
            ->putJson("/api/v1/doctors/{$doctor->id}", [
                'specialization' => 'Oncology',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_doctor()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => null]);
    }

    public function test_validation_error_on_create()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/doctors', [
                // missing user_id and specialization
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_doctor_show_returns_nested_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id, 'specialization' => 'Cardiology']);

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $doctor->user->id)
            ->assertJsonPath('data.specialization', 'Cardiology');
    }

    public function test_doctor_can_see_own_appointments()
    {
        [$user, $token] = $this->authenticateAs('doctor');
        $doctor = \App\Models\Doctor::factory()->create(['user_id' => $user->id]);
        $patient1 = \App\Models\Patient::factory()->create();
        $patient2 = \App\Models\Patient::factory()->create();
        // Appointments for this doctor
        $appt1 = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient1->id,
            'scheduled_at' => now()->addDay()->setTime(9, 30),
            'status' => 'scheduled',
        ]);
        $appt2 = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
            'scheduled_at' => now()->addDays(2)->setTime(10, 0),
            'status' => 'scheduled',
        ]);
        // Appointment for another doctor
        $otherDoctor = \App\Models\Doctor::factory()->create();
        $apptOther = \App\Models\Appointment::create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $patient1->id,
            'scheduled_at' => now()->addDays(3)->setTime(11, 0),
            'status' => 'scheduled',
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/appointments');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($appt1->id, $ids);
        $this->assertContains($appt2->id, $ids);
        $this->assertNotContains($apptOther->id, $ids);
    }

    public function test_doctor_can_see_own_queue_entries()
    {
        [$user, $token] = $this->authenticateAs('doctor');
        $doctor = \App\Models\Doctor::factory()->create(['user_id' => $user->id]);
        $patient1 = \App\Models\Patient::factory()->create();
        $patient2 = \App\Models\Patient::factory()->create();
        // Queue entries for this doctor
        $queue1 = \App\Models\Queue::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient1->id,
            'position' => 1,
            'status' => 'waiting',
        ]);
        $queue2 = \App\Models\Queue::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
            'position' => 2,
            'status' => 'waiting',
        ]);
        // Queue entry for another doctor
        $otherDoctor = \App\Models\Doctor::factory()->create();
        $queueOther = \App\Models\Queue::create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $patient1->id,
            'position' => 1,
            'status' => 'waiting',
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/queues');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($queue1->id, $ids);
        $this->assertContains($queue2->id, $ids);
        $this->assertNotContains($queueOther->id, $ids);
    }
} 