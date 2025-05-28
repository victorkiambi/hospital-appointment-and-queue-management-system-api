<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Events\PatientJoinedQueue;
use App\Events\PatientLeftQueue;
use App\Events\PatientCalled;
use App\Events\QueuePositionChanged;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueResource;

class QueueController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Queue::class, 'queue');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        $query = Queue::with(['doctor.user', 'patient.user'])->orderBy('doctor_id')->orderBy('position');

        if ($user->role === 'patient') {
            if ($user->patient) {
                $query->where('patient_id', $user->patient->id);
            } else {
                return response()->json([
                    'data' => [],
                    'meta' => null,
                    'message' => 'No queue entries found for this patient.',
                    'errors' => null,
                ]);
            }
        } elseif ($user->role === 'doctor') {
            if ($user->doctor) {
                $query->where('doctor_id', $user->doctor->id);
            } else {
                return response()->json([
                    'data' => [],
                    'meta' => null,
                    'message' => 'No queue entries found for this doctor.',
                    'errors' => null,
                ]);
            }
        } else {
            // Admin: can filter by doctor_id if provided
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
        }

        $queues = $query->paginate($perPage);
        $meta = [
            'total' => $queues->total(),
            'per_page' => $queues->perPage(),
            'current_page' => $queues->currentPage(),
            'last_page' => $queues->lastPage(),
        ];

        return response()->json([
            'data' => QueueResource::collection($queues->items()),
            'meta' => $meta,
            'message' => 'Queue entries fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQueueRequest $request)
    {
        $validated = $request->validated();
        // Prevent duplicate queue entry for same doctor/patient with status 'waiting'
        $exists = Queue::where('doctor_id', $validated['doctor_id'])
            ->where('patient_id', $validated['patient_id'])
            ->where('status', 'waiting')
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Patient is already in the queue for this doctor.',
                'errors' => ['patient_id' => ['Patient is already in the queue for this doctor.']],
            ], 409);
        }
        // Assign next position in queue for this doctor
        $maxPosition = Queue::where('doctor_id', $validated['doctor_id'])
            ->where('status', 'waiting')
            ->max('position');
        $position = $maxPosition ? $maxPosition + 1 : 1;
        $queue = Queue::create([
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'position' => $position,
            'status' => 'waiting',
        ]);
        event(new PatientJoinedQueue($queue->withoutRelations()));
        return response()->json([
            'data' => new QueueResource($queue->load(['doctor', 'patient'])),
            'message' => 'Patient added to queue',
            'errors' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $queue = Queue::with(['doctor.user', 'patient.user'])->find($id);
        if (!$queue) {
            return response()->json([
                'message' => 'Queue entry not found',
                'errors' => ['id' => ['Queue entry not found']],
            ], 404);
        }
        return response()->json([
            'data' => new QueueResource($queue),
            'message' => 'Queue entry fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQueueRequest $request, Queue $queue)
    {
        $validated = $request->validated();
        $originalStatus = $queue->status;
        $originalPosition = $queue->position;
        $queue->update($request->only(['status', 'position', 'called_at']));
        // Dispatch granular events
        if ($originalStatus !== $queue->status && $queue->status === 'called') {
            event(new PatientCalled($queue->withoutRelations()));
        } elseif ($originalPosition !== $queue->position) {
            event(new QueuePositionChanged($queue->withoutRelations()));
        }
        return response()->json([
            'data' => new QueueResource($queue->load(['doctor', 'patient'])),
            'message' => 'Queue entry updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Queue $queue)
    {
        event(new PatientLeftQueue($queue->withoutRelations()));
        $doctorId = $queue->doctor_id;
        $queue->delete();

        // Reorder remaining queue entries for this doctor
        $remaining = Queue::where('doctor_id', $doctorId)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->get();

        $position = 1;
        foreach ($remaining as $entry) {
            $entry->position = $position++;
            $entry->save();
        }

        return response()->json([
            'data' => null,
            'message' => 'Queue entry deleted successfully',
            'errors' => null,
        ]);
    }
}
