<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'Login successful',
            'errors' => null,
        ]);
    }
}