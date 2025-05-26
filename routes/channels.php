<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('queue.doctor.{doctorId}', function (User $user, $doctorId) {
    return ($user->role === 'doctor' && $user->doctor && $user->doctor->id == $doctorId)
        || $user->role === 'admin';
});

Broadcast::channel('queue.patient.{patientId}', function (User $user, $patientId) {
    return ($user->role === 'patient' && $user->patient && $user->patient->id == $patientId)
        || $user->role === 'admin';
});
