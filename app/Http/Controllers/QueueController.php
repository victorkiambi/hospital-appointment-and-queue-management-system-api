<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class QueueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Queue::with(['doctor.user', 'patient.user'])->orderBy('doctor_id')->orderBy('position');

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $queues = $query->get();

        return response()->json([
            'data' => $queues,
            'message' => 'Queue entries fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Prevent duplicate queue entry for same doctor/patient with status 'waiting'
        $exists = Queue::where('doctor_id', $request->doctor_id)
            ->where('patient_id', $request->patient_id)
            ->where('status', 'waiting')
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Patient is already in the queue for this doctor.',
                'errors' => ['patient_id' => ['Patient is already in the queue for this doctor.']],
            ], 409);
        }
        // Assign next position in queue for this doctor
        $maxPosition = Queue::where('doctor_id', $request->doctor_id)
            ->where('status', 'waiting')
            ->max('position');
        $position = $maxPosition ? $maxPosition + 1 : 1;
        $queue = Queue::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'position' => $position,
            'status' => 'waiting',
        ]);
        return response()->json([
            'data' => $queue,
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
            'data' => $queue,
            'message' => 'Queue entry fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $queue = Queue::find($id);
        if (!$queue) {
            return response()->json([
                'message' => 'Queue entry not found',
                'errors' => ['id' => ['Queue entry not found']],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|string|in:waiting,called,completed,cancelled',
            'position' => 'sometimes|required|integer|min:1',
            'called_at' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $queue->update($request->only(['status', 'position', 'called_at']));
        return response()->json([
            'data' => $queue,
            'message' => 'Queue entry updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $queue = Queue::find($id);
        if (!$queue) {
            return response()->json([
                'message' => 'Queue entry not found',
                'errors' => ['id' => ['Queue entry not found']],
            ], 404);
        }
        $queue->delete();
        return response()->json([
            'data' => null,
            'message' => 'Queue entry deleted successfully',
            'errors' => null,
        ]);
    }
}
