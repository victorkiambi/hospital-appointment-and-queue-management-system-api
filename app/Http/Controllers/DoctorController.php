<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Doctor::class, 'doctor');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::with('user')->get();
        return response()->json([
            'data' => $doctors,
            'message' => 'Doctors fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:doctors,user_id',
            'specialization' => 'required|string|max:255',
            'availability' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $doctor = Doctor::create([
            'user_id' => $request->user_id,
            'specialization' => $request->specialization,
            'availability' => $request->availability,
        ]);
        return response()->json([
            'data' => $doctor,
            'message' => 'Doctor created successfully',
            'errors' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $doctor = Doctor::with('user')->find($id);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'errors' => ['id' => ['Doctor not found']],
            ], 404);
        }
        return response()->json([
            'data' => $doctor,
            'message' => 'Doctor fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'errors' => ['id' => ['Doctor not found']],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'specialization' => 'sometimes|required|string|max:255',
            'availability' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $doctor->update($request->only(['specialization', 'availability']));
        return response()->json([
            'data' => $doctor,
            'message' => 'Doctor updated successfully',
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'errors' => ['id' => ['Doctor not found']],
            ], 404);
        }
        $doctor->delete();
        return response()->json([
            'data' => null,
            'message' => 'Doctor deleted successfully',
            'errors' => null,
        ]);
    }
}
