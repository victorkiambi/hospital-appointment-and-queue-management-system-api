<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Queue;

class AdminStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden',
                    'errors' => ['auth' => ['Admin access required']],
                ], 403);
            }
            return $next($request);
        });
    }

    public function summary()
    {
        $stats = [
            'total_users' => User::count(),
            'total_doctors' => Doctor::count(),
            'total_patients' => Patient::count(),
            'total_appointments' => Appointment::count(),
            'total_queues' => Queue::count(),
        ];
        return response()->json([
            'data' => $stats,
            'message' => 'Statistics fetched successfully',
            'errors' => null,
        ]);
    }
} 