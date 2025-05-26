<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'doctor']),
            'specialization' => $this->faker->randomElement(['Cardiology', 'Dermatology', 'Neurology', 'Pediatrics', 'Oncology']),
            'availability' => json_encode([
                [ 'day' => 'Monday', 'start' => '09:00', 'end' => '12:00' ],
                [ 'day' => 'Monday', 'start' => '14:00', 'end' => '17:00' ],
                [ 'day' => 'Tuesday', 'start' => '09:00', 'end' => '12:00' ],
            ]),
        ];
    }
}
