<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $userData = $user->toArray();
        $userData['doctor_id'] = $user->doctor ? $user->doctor->id : null;
        $userData['patient_id'] = $user->patient ? $user->patient->id : null;
        return response()->json([
            'data' => $userData,
            'message' => 'User profile fetched successfully',
            'errors' => null,
        ]);
    }
} 