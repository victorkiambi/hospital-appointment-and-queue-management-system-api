<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        $query = Appointment::with(['doctor.user', 'patient.user']);

        if ($user->role === 'patient') {
            if ($user->patient) {
                $query->where('patient_id', $user->patient->id);
            } else {
                return response()->json([
                    'data' => [],
                    'meta' => null,
                    'message' => 'No appointments found for this patient.',
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
                    'message' => 'No appointments found for this doctor.',
                    'errors' => null,
                ]);
            }
        } else {
            // Admin: can filter by doctor_id or patient_id if provided
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
        }

        $appointments = $query->paginate($perPage);
        $meta = [
            'total' => $appointments->total(),
            'per_page' => $appointments->perPage(),
            'current_page' => $appointments->currentPage(),
            'last_page' => $appointments->lastPage(),
        ];

        return response()->json([
            'data' => $appointments->items(),
            'meta' => $meta,
            'message' => 'Appointments fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // If patient, force patient_id to their own
        if ($user->role === 'patient') {
            $patient = $user->patient;
            if (!$patient) {
                return response()->json([
                    'message' => 'User is not a patient.',
                    'errors' => ['user' => ['User is not a patient.']],
                ], 403);
            }
            $patient_id = $patient->id;
        } else {
            $patient_id = $request->patient_id;
            if (!$patient_id) {
                return response()->json([
                    'message' => 'patient_id is required for admin.',
                    'errors' => ['patient_id' => ['patient_id is required for admin.']],
                ], 422);
            }
        }
        // Check doctor's availability
        $doctor = Doctor::find($request->doctor_id);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found.',
                'errors' => ['doctor_id' => ['Doctor not found.']],
            ], 404);
        }
        if (!$this->isDoctorAvailable($doctor, $request->scheduled_at)) {
            return response()->json([
                'message' => 'Doctor is not available at the selected time.',
                'errors' => ['scheduled_at' => ['Doctor is not available at the selected time.']],
            ], 409);
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
            'patient_id' => $patient_id,
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

    /**
     * Check if doctor is available at the given datetime.
     */
    protected function isDoctorAvailable($doctor, $datetime)
    {
        if (!$doctor->availability) return false;
        $availability = is_array($doctor->availability) ? $doctor->availability : json_decode($doctor->availability, true);
        $dt = \Carbon\Carbon::parse($datetime);
        $day = $dt->format('l'); // e.g., 'Monday'
        $time = $dt->format('H:i');
        foreach ($availability as $slot) {
            if (!isset($slot['day'], $slot['start'], $slot['end'])) {
                continue; // skip malformed slot
            }
            if ($slot['day'] === $day && $time >= $slot['start'] && $time < $slot['end']) {
                return true;
            }
        }
        return false;
    }
}
