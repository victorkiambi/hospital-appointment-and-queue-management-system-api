<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientResource;

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
            // Admin: can filter by doctor_id, patient_id, status, date, and search
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('date')) {
                $query->whereDate('scheduled_at', $request->date);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhereHas('patient.user', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('doctor.user', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%$search%");
                        });
                });
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
            'data' => AppointmentResource::collection($appointments->items()),
            'meta' => $meta,
            'message' => 'Appointments fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['patient', 'admin'])) {
            return response()->json([
                'message' => 'Only patients and admins can book appointments.',
                'errors' => ['user' => ['Only patients and admins can book appointments.']],
            ], 403);
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
        $appointment->load(['doctor.user', 'patient.user']);

        // Auto-add to queue for same-day appointments
        $scheduledDate = Carbon::parse($appointment->scheduled_at)->toDateString();
        $today = Carbon::now()->toDateString();
        if ($scheduledDate === $today) {
            $exists = Queue::where('doctor_id', $appointment->doctor_id)
                ->where('patient_id', $appointment->patient_id)
                ->where('status', 'waiting')
                ->exists();
            if (!$exists) {
                $maxPosition = Queue::where('doctor_id', $appointment->doctor_id)
                    ->where('status', 'waiting')
                    ->max('position');
                $position = $maxPosition ? $maxPosition + 1 : 1;
                Queue::create([
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'position' => $position,
                    'status' => 'waiting',
                ]);
            }
        }

        return response()->json([
            'data' => new AppointmentResource($appointment),
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
            'data' => new AppointmentResource($appointment),
            'message' => 'Appointment fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
                'errors' => ['id' => ['Appointment not found']],
            ], 404);
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
        $appointment->update($request->validated());
        $appointment->load(['doctor.user', 'patient.user']);
        return response()->json([
            'data' => new AppointmentResource($appointment),
            'message' => 'Appointment updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        // Remove related queue entry if it exists
        Queue::where('doctor_id', $appointment->doctor_id)
            ->where('patient_id', $appointment->patient_id)
            ->where('status', 'waiting')
            ->delete();

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
        $dt = Carbon::parse($datetime);
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
