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
    public function index()
    {
        $patients = Patient::with('user')->get();
        return response()->json([
            'data' => $patients,
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
    public function show($id)
    {
        $patient = Patient::with('user')->find($id);
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found',
                'errors' => ['id' => ['Patient not found']],
            ], 404);
        }
        return response()->json([
            'data' => $patient,
            'message' => 'Patient fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $patient = Patient::find($id);
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found',
                'errors' => ['id' => ['Patient not found']],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'medical_record_number' => 'sometimes|required|string|max:255|unique:patients,medical_record_number,' . $id,
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
    public function destroy($id)
    {
        $patient = Patient::find($id);
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found',
                'errors' => ['id' => ['Patient not found']],
            ], 404);
        }
        $patient->delete();
        return response()->json([
            'data' => null,
            'message' => 'Patient deleted successfully',
            'errors' => null,
        ]);
    }
}
