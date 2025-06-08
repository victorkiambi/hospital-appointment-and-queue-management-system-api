<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'doctor' || $user->role === 'patient';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // An admin can view any appointment.
        if ($user->role === 'admin') {
            return true;
        }

        // The doctor associated with the appointment can view it.
        if ($user->doctor && $user->doctor->id === $appointment->doctor_id) {
            return true;
        }

        // The patient who booked the appointment can view it.
        if ($user->patient && $user->patient->id === $appointment->patient_id) {
            return true;
        }

        // Deny all other requests.
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'patient';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // An admin can update any appointment.
        if ($user->role === 'admin') {
            return true;
        }

        // A doctor can update an appointment they are assigned to.
        if ($user->doctor && $user->doctor->id === $appointment->doctor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role === 'admin'
            || ($user->patient && $user->patient->id === $appointment->patient_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
} 