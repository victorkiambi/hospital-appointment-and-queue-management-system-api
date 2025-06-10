<?php

namespace App\Policies;

use App\Models\Queue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QueuePolicy
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
    public function view(User $user, Queue $queue): bool
    {
        // Admin or the assigned doctor can view the queue entry.
        if ($user->role === 'admin' || ($user->doctor && $user->doctor->id === $queue->doctor_id)) {
            return true;
        }

        // The patient in the queue can view their own entry.
        if ($user->patient && $user->patient->id === $queue->patient_id) {
            return true;
        }

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
    public function update(User $user, Queue $queue): bool
    {
        return $user->role === 'admin' ||
            ($user->doctor && $user->doctor->id === $queue->doctor_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Queue $queue): bool
    {
        return $user->role === 'admin' ||
            ($user->patient && $user->patient->id === $queue->patient_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Queue $queue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Queue $queue): bool
    {
        return false;
    }
} 