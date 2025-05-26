<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Doctor;
use App\Policies\DoctorPolicy;
use App\Models\Patient;
use App\Policies\PatientPolicy;
use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use App\Models\Queue;
use App\Policies\QueuePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Policy registration is automatic in Laravel 9+ with the $policies property.
    }

    protected $policies = [
        Doctor::class => DoctorPolicy::class,
        Patient::class => PatientPolicy::class,
        Appointment::class => AppointmentPolicy::class,
        Queue::class => QueuePolicy::class,
    ];
}
