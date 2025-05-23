<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Appointment::with(['doctor.user', 'patient.user']);

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        $appointments = $query->get();

        return response()->json([
            'data' => $appointments,
            'message' => 'Appointments fetched successfully',
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
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Prevent double-booking for doctor at the same time
        $exists = Appointment::where('doctor_id', $request->doctor_id)
            ->where('scheduled_at', $request->scheduled_at)
            ->where('status', 'scheduled')
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Double-booking detected for doctor at this time.',
                'errors' => ['scheduled_at' => ['Doctor already has an appointment at this time.']],
            ], 409);
        }
        $appointment = Appointment::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'scheduled_at' => $request->scheduled_at,
            'status' => $request->status ?? 'scheduled',
        ]);
        return response()->json([
            'data' => $appointment,
            'message' => 'Appointment created successfully',
            'errors' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->find($id);
        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
                'errors' => ['id' => ['Appointment not found']],
            ], 404);
        }
        return response()->json([
            'data' => $appointment,
            'message' => 'Appointment fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
                'errors' => ['id' => ['Appointment not found']],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'patient_id' => 'sometimes|required|exists:patients,id',
            'scheduled_at' => 'sometimes|required|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Prevent double-booking for doctor at the same time (if changing doctor or scheduled_at)
        if ($request->has(['doctor_id', 'scheduled_at'])) {
            $exists = Appointment::where('doctor_id', $request->doctor_id)
                ->where('scheduled_at', $request->scheduled_at)
                ->where('status', 'scheduled')
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Double-booking detected for doctor at this time.',
                    'errors' => ['scheduled_at' => ['Doctor already has an appointment at this time.']],
                ], 409);
            }
        }
        $appointment->update($request->only(['doctor_id', 'patient_id', 'scheduled_at', 'status']));
        return response()->json([
            'data' => $appointment,
            'message' => 'Appointment updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
                'errors' => ['id' => ['Appointment not found']],
            ], 404);
        }
        $appointment->delete();
        return response()->json([
            'data' => null,
            'message' => 'Appointment deleted successfully',
            'errors' => null,
        ]);
    }
}
