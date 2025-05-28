<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateAs($role = 'admin')
    {
        $user = User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $response->json('token');
        return [$user, $token];
    }

    public function test_admin_can_list_patients()
    {
        [$admin, $token] = $this->authenticateAs('admin');
        Patient::factory()->count(3)->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/patients');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'message', 'errors']);
    }

    public function test_patient_can_only_see_own_record()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/patients');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_patient()
    {
        [$admin, $token] = $this->authenticateAs('admin');
        $user = User::factory()->create(['role' => 'patient']);
        $payload = [
            'user_id' => $user->id,
        ];
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/patients', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.medical_record_number', \App\Models\Patient::generateMedicalRecordNumber($user->id));
    }

    public function test_patient_cannot_create_patient()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $otherUser = User::factory()->create(['role' => 'patient']);
        $payload = [
            'user_id' => $otherUser->id,
        ];
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/patients', $payload);
        $response->assertStatus(403);
    }

    public function test_admin_can_view_patient()
    {
        [$admin, $token] = $this->authenticateAs('admin');
        $patient = Patient::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/patients/' . $patient->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $patient->id);
    }

    public function test_admin_can_update_patient()
    {
        [$admin, $token] = $this->authenticateAs('admin');
        $patient = Patient::factory()->create();
        $payload = ['medical_record_number' => 'MRN-UPDATED'];
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/patients/' . $patient->id, $payload);
        $response->assertStatus(200)
            ->assertJsonPath('data.medical_record_number', 'MRN-UPDATED');
    }

    public function test_admin_can_delete_patient()
    {
        [$admin, $token] = $this->authenticateAs('admin');
        $patient = Patient::factory()->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/patients/' . $patient->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    public function test_patient_cannot_update_or_delete_patient()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = Patient::factory()->create();
        $payload = ['medical_record_number' => 'MRN-FAIL'];
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/patients/' . $patient->id, $payload);
        $updateResponse->assertStatus(403);
        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/patients/' . $patient->id);
        $deleteResponse->assertStatus(403);
    }

    public function test_patient_can_see_own_appointments()
    {
        [$user, $token] = $this->authenticateAs('patient');
        $patient = \App\Models\Patient::factory()->create(['user_id' => $user->id]);
        $doctor = \App\Models\Doctor::factory()->create();
        // Create appointments for this patient
        $appt1 = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay()->setTime(9, 30),
            'status' => 'scheduled',
        ]);
        $appt2 = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDays(2)->setTime(10, 0),
            'status' => 'scheduled',
        ]);
        // Create an appointment for another patient
        $otherPatient = \App\Models\Patient::factory()->create();
        $apptOther = \App\Models\Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $otherPatient->id,
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
} 