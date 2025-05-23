<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Doctor;
use App\Policies\DoctorPolicy;

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
    ];
}
