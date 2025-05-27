<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use App\Events\PatientJoinedQueue;
use App\Events\PatientLeftQueue;
use App\Events\PatientCalled;
use App\Events\QueuePositionChanged;

class QueueApiEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_joining_queue_dispatches_patient_joined_event()
    {
        Event::fake([PatientJoinedQueue::class]);
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $payload = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
        ];
        $user = $patient->user; // Patient user
        $response = $this->actingAs($user)->postJson('/api/v1/queues', $payload);
        $response->assertStatus(201);
        Event::assertDispatched(PatientJoinedQueue::class);
        // Fetch the created queue and assert linkage
        $queue = \App\Models\Queue::where('doctor_id', $doctor->id)->where('patient_id', $patient->id)->first();
        $this->assertNotNull($queue, 'Queue should exist');
        $this->assertEquals('patient', $user->role, 'User role should be patient');
        $this->assertEquals($user->id, $patient->user_id, 'Patient user_id should match user id');
        $this->assertEquals($user->id, $queue->patient->user_id, 'Queue patient user_id should match user id');
    }

    public function test_patient_leaving_queue_dispatches_patient_left_event()
    {
        Event::fake([PatientLeftQueue::class]);
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $queue = Queue::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id]);
        $queue = $queue->fresh(['doctor', 'patient']);
        $user = $patient->user; // Patient user
        // Debug assertions
        $this->assertEquals('patient', $user->role, 'User role should be patient');
        $this->assertEquals($user->id, $patient->user_id, 'Patient user_id should match user id');
        $this->assertEquals($user->id, $queue->patient->user_id, 'Queue patient user_id should match user id');
        $response = $this->actingAs($user)->deleteJson('/api/v1/queues/' . $queue->id);
        $response->assertStatus(200);
        Event::assertDispatched(PatientLeftQueue::class);
    }

    public function test_patient_called_dispatches_patient_called_event()
    {
        Event::fake([PatientCalled::class]);
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $queue = Queue::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'status' => 'waiting']);
        $queue = $queue->fresh(['doctor', 'patient']);
        $user = $doctor->user; // Doctor user
        // Debug assertions
        $this->assertEquals('doctor', $user->role, 'User role should be doctor');
        $this->assertEquals($user->id, $doctor->user_id, 'Doctor user_id should match user id');
        $this->assertEquals($user->id, $queue->doctor->user_id, 'Queue doctor user_id should match user id');
        $payload = ['status' => 'called'];
        $response = $this->actingAs($user)->putJson('/api/v1/queues/' . $queue->id, $payload);
        $response->assertStatus(200);
        Event::assertDispatched(PatientCalled::class);
    }

    public function test_queue_position_changed_dispatches_queue_position_changed_event()
    {
        Event::fake([QueuePositionChanged::class]);
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $queue = Queue::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'position' => 1]);
        $queue = $queue->fresh(['doctor', 'patient']);
        $user = $doctor->user; // Doctor user
        // Debug assertions
        $this->assertEquals('doctor', $user->role, 'User role should be doctor');
        $this->assertEquals($user->id, $doctor->user_id, 'Doctor user_id should match user id');
        $this->assertEquals($user->id, $queue->doctor->user_id, 'Queue doctor user_id should match user id');
        $payload = ['position' => 2];
        $response = $this->actingAs($user)->putJson('/api/v1/queues/' . $queue->id, $payload);
        $response->assertStatus(200);
        Event::assertDispatched(QueuePositionChanged::class);
    }

    public function test_patient_cannot_be_added_to_same_doctor_queue_twice_with_waiting_status()
    {
        $doctor = \App\Models\Doctor::factory()->create();
        $patient = \App\Models\Patient::factory()->create();
        $user = $patient->user;
        $payload = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
        ];
        // First entry should succeed
        $response1 = $this->actingAs($user)->postJson('/api/v1/queues', $payload);
        $response1->assertStatus(201);
        // Second entry should fail with 409
        $response2 = $this->actingAs($user)->postJson('/api/v1/queues', $payload);
        $response2->assertStatus(409)
            ->assertJsonPath('message', 'Patient is already in the queue for this doctor.');
    }

    public function test_new_queue_entries_are_assigned_correct_position()
    {
        $doctor = \App\Models\Doctor::factory()->create();
        $patient1 = \App\Models\Patient::factory()->create();
        $patient2 = \App\Models\Patient::factory()->create();
        $user1 = $patient1->user;
        $user2 = $patient2->user;
        $payload1 = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient1->id,
        ];
        $payload2 = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient2->id,
        ];
        $response1 = $this->actingAs($user1)->postJson('/api/v1/queues', $payload1);
        $response1->assertStatus(201);
        $queue1 = \App\Models\Queue::where('doctor_id', $doctor->id)->where('patient_id', $patient1->id)->first();
        $this->assertEquals(1, $queue1->position);
        $response2 = $this->actingAs($user2)->postJson('/api/v1/queues', $payload2);
        $response2->assertStatus(201);
        $queue2 = \App\Models\Queue::where('doctor_id', $doctor->id)->where('patient_id', $patient2->id)->first();
        $this->assertEquals(2, $queue2->position);
    }

    public function test_queue_order_updates_when_patient_leaves()
    {
        $doctor = \App\Models\Doctor::factory()->create();
        $patient1 = \App\Models\Patient::factory()->create();
        $patient2 = \App\Models\Patient::factory()->create();
        $patient3 = \App\Models\Patient::factory()->create();
        $user1 = $patient1->user;
        $user2 = $patient2->user;
        $user3 = $patient3->user;
        // Add three patients to the queue
        $q1 = \App\Models\Queue::create(['doctor_id' => $doctor->id, 'patient_id' => $patient1->id, 'position' => 1, 'status' => 'waiting']);
        $q2 = \App\Models\Queue::create(['doctor_id' => $doctor->id, 'patient_id' => $patient2->id, 'position' => 2, 'status' => 'waiting']);
        $q3 = \App\Models\Queue::create(['doctor_id' => $doctor->id, 'patient_id' => $patient3->id, 'position' => 3, 'status' => 'waiting']);
        // Patient 2 leaves
        $response = $this->actingAs($user2)->deleteJson('/api/v1/queues/' . $q2->id);
        $response->assertStatus(200);
        // Refresh queues
        $q1 = $q1->fresh();
        $q3 = $q3->fresh();
        // After patient 2 leaves, positions should be 1 and 2
        $this->assertEquals(1, $q1->position);
        $this->assertEquals(2, $q3->position);
    }
} 