<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Events\PatientJoinedQueue;
use App\Events\PatientLeftQueue;
use App\Events\PatientCalled;
use App\Events\QueuePositionChanged;
use App\Models\Queue;

class QueueEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_broadcasts_patient_joined_queue_event()
    {
        Event::fake([PatientJoinedQueue::class]);
        $queue = Queue::factory()->create();
        event(new PatientJoinedQueue($queue));
        Event::assertDispatched(PatientJoinedQueue::class, function ($event) use ($queue) {
            return $event->queue->id === $queue->id;
        });
    }

    /** @test */
    public function it_broadcasts_patient_left_queue_event()
    {
        Event::fake([PatientLeftQueue::class]);
        $queue = Queue::factory()->create();
        event(new PatientLeftQueue($queue));
        Event::assertDispatched(PatientLeftQueue::class, function ($event) use ($queue) {
            return $event->queue->id === $queue->id;
        });
    }

    /** @test */
    public function it_broadcasts_patient_called_event()
    {
        Event::fake([PatientCalled::class]);
        $queue = Queue::factory()->create(['status' => 'called']);
        event(new PatientCalled($queue));
        Event::assertDispatched(PatientCalled::class, function ($event) use ($queue) {
            return $event->queue->id === $queue->id;
        });
    }

    /** @test */
    public function it_broadcasts_queue_position_changed_event()
    {
        Event::fake([QueuePositionChanged::class]);
        $queue = Queue::factory()->create(['position' => 1]);
        $queue->position = 2;
        event(new QueuePositionChanged($queue));
        Event::assertDispatched(QueuePositionChanged::class, function ($event) use ($queue) {
            return $event->queue->id === $queue->id && $event->queue->position === 2;
        });
    }

    public function test_patient_joined_queue_event_broadcasts_on_correct_channels_and_payload()
    {
        $queue = Queue::factory()->create();
        $event = new PatientJoinedQueue($queue);
        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();
        $expectedDoctorChannel = 'private-queue.doctor.' . $queue->doctor_id;
        $expectedPatientChannel = 'private-queue.patient.' . $queue->patient_id;
        $channelNames = array_map(fn($c) => method_exists($c, 'name') ? $c->name() : (string)$c, $channels);
        $this->assertContains($expectedDoctorChannel, $channelNames);
        $this->assertContains($expectedPatientChannel, $channelNames);
        $this->assertArrayHasKey('queue', $payload);
        $this->assertEquals($queue->toArray(), $payload['queue']);
    }

    public function test_patient_left_queue_event_broadcasts_on_correct_channels_and_payload()
    {
        $queue = Queue::factory()->create();
        $event = new PatientLeftQueue($queue);
        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();
        $expectedDoctorChannel = 'private-queue.doctor.' . $queue->doctor_id;
        $expectedPatientChannel = 'private-queue.patient.' . $queue->patient_id;
        $channelNames = array_map(fn($c) => method_exists($c, 'name') ? $c->name() : (string)$c, $channels);
        $this->assertContains($expectedDoctorChannel, $channelNames);
        $this->assertContains($expectedPatientChannel, $channelNames);
        $this->assertArrayHasKey('queue', $payload);
        $this->assertEquals($queue->toArray(), $payload['queue']);
    }

    public function test_patient_called_event_broadcasts_on_correct_channels_and_payload()
    {
        $queue = Queue::factory()->create(['status' => 'called']);
        $event = new PatientCalled($queue);
        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();
        $expectedDoctorChannel = 'private-queue.doctor.' . $queue->doctor_id;
        $expectedPatientChannel = 'private-queue.patient.' . $queue->patient_id;
        $channelNames = array_map(fn($c) => method_exists($c, 'name') ? $c->name() : (string)$c, $channels);
        $this->assertContains($expectedDoctorChannel, $channelNames);
        $this->assertContains($expectedPatientChannel, $channelNames);
        $this->assertArrayHasKey('queue', $payload);
        $this->assertEquals($queue->toArray(), $payload['queue']);
    }

    public function test_queue_position_changed_event_broadcasts_on_correct_channels_and_payload()
    {
        $queue = Queue::factory()->create(['position' => 1]);
        $queue->position = 2;
        $event = new QueuePositionChanged($queue);
        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();
        $expectedDoctorChannel = 'private-queue.doctor.' . $queue->doctor_id;
        $expectedPatientChannel = 'private-queue.patient.' . $queue->patient_id;
        $channelNames = array_map(fn($c) => method_exists($c, 'name') ? $c->name() : (string)$c, $channels);
        $this->assertContains($expectedDoctorChannel, $channelNames);
        $this->assertContains($expectedPatientChannel, $channelNames);
        $this->assertArrayHasKey('queue', $payload);
        $this->assertEquals($queue->toArray(), $payload['queue']);
    }
} 