<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientResource;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['doctor.user', 'patient.user']);
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
        if ($user->doctor) {
            $data['doctor'] = new DoctorResource($user->doctor);
        }
        if ($user->patient) {
            $data['patient'] = new PatientResource($user->patient);
        }
        return response()->json([
            'data' => $data,
            'message' => 'User profile fetched successfully',
            'errors' => null,
        ]);
    }
} 