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
    public function index(Request $request)
    {
        $user = request()->user();
        $perPage = $request->input('per_page', 20);
        if ($user->role === 'doctor') {
            if ($user->doctor) {
                $doctors = Doctor::with('user')->where('id', $user->doctor->id)->get();
                $meta = null;
            } else {
                $doctors = collect();
                $meta = null;
            }
        } elseif ($user->role === 'admin' || $user->role === 'patient') {
            $doctors = Doctor::with('user')->paginate($perPage);
            $meta = [
                'total' => $doctors->total(),
                'per_page' => $doctors->perPage(),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
            ];
            $doctors = $doctors->items();
        } else {
            $doctors = collect();
            $meta = null;
        }
        return response()->json([
            'data' => $doctors,
            'meta' => $meta,
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
