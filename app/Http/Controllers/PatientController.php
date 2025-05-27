<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = request()->user();
        $perPage = $request->input('per_page', 20);
        if ($user->role === 'patient') {
            if ($user->patient) {
                $patients = Patient::with('user')->where('id', $user->patient->id)->get();
                $meta = null;
            } else {
                $patients = collect();
                $meta = null;
            }
        } elseif ($user->role === 'admin') {
            $patients = Patient::with('user')->paginate($perPage);
            $meta = [
                'total' => $patients->total(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
            ];
            $patients = $patients->items();
        } else {
            $patients = collect();
            $meta = null;
        }
        return response()->json([
            'data' => $patients,
            'meta' => $meta,
            'message' => 'Patients fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:patients,user_id',
            'medical_record_number' => 'required|string|max:255|unique:patients,medical_record_number',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $patient = Patient::create([
            'user_id' => $request->user_id,
            'medical_record_number' => $request->medical_record_number,
        ]);
        return response()->json([
            'data' => $patient,
            'message' => 'Patient created successfully',
            'errors' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        return response()->json([
            'data' => $patient->load('user'),
            'message' => 'Patient fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        $validator = Validator::make($request->all(), [
            'medical_record_number' => 'sometimes|required|string|max:255|unique:patients,medical_record_number,' . $patient->id,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $patient->update($request->only(['medical_record_number']));
        return response()->json([
            'data' => $patient,
            'message' => 'Patient updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json([
            'data' => null,
            'message' => 'Patient deleted successfully',
            'errors' => null,
        ]);
    }
}
