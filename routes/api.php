<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('login', [LoginController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LogoutController::class, 'logout']);
        Route::get('user', [UserController::class, 'profile']);
        Route::apiResource('doctors', \App\Http\Controllers\DoctorController::class);
        Route::apiResource('patients', \App\Http\Controllers\PatientController::class);
        Route::apiResource('appointments', \App\Http\Controllers\AppointmentController::class);
        Route::apiResource('queues', \App\Http\Controllers\QueueController::class);
        Route::apiResource('users', \App\Http\Controllers\AdminUserController::class);
        Route::get('admin/statistics/summary', [\App\Http\Controllers\AdminStatisticsController::class, 'summary']);
        Route::post('admin/users/doctor', [\App\Http\Controllers\AdminUserController::class, 'storeDoctor']);
    });
});

