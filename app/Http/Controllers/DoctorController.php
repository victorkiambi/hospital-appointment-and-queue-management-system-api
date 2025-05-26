<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;

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
            $paginated = Doctor::with('user')->paginate($perPage);
            $meta = [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ];
            $doctors = $paginated->items();
        } else {
            $doctors = collect();
            $meta = null;
        }
        return response()->json([
            'data' => DoctorResource::collection($doctors),
            'meta' => $meta,
            'message' => 'Doctors fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorRequest $request)
    {
        $doctor = Doctor::create($request->validated());
        $doctor->load('user');
        return response()->json([
            'data' => new DoctorResource($doctor),
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
            'data' => new DoctorResource($doctor),
            'message' => 'Doctor fetched successfully',
            'errors' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'errors' => ['id' => ['Doctor not found']],
            ], 404);
        }
        $doctor->update($request->validated());
        $doctor->load('user');
        return response()->json([
            'data' => new DoctorResource($doctor),
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
