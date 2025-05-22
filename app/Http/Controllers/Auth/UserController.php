<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json([
            'data' => $request->user(),
            'message' => 'User profile fetched successfully',
            'errors' => null,
        ]);
    }
} 