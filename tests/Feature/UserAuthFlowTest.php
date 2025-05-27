<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token_and_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'created_at', 'updated_at', 'doctor_id', 'patient_id'],
                ],
                'message',
                'errors',
            ]);
    }

    public function test_login_with_invalid_credentials_fails()
    {
        $user = User::factory()->create([
            'email' => 'fail@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'fail@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_authenticated_user_can_fetch_profile_with_role_details()
    {
        $doctor = Doctor::factory()->create();
        $user = $doctor->user;
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'role', 'created_at', 'updated_at', 'doctor'
                ],
                'message',
                'errors',
            ])
            ->assertJsonPath('data.doctor.id', $doctor->id);
    }

    public function test_authenticated_patient_can_fetch_profile_with_role_details()
    {
        $patient = Patient::factory()->create();
        $user = $patient->user;
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'role', 'created_at', 'updated_at', 'patient'
                ],
                'message',
                'errors',
            ])
            ->assertJsonPath('data.patient.id', $patient->id);
    }

    public function test_logout_revokes_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $this->app->make('auth')->guard()->forgetUser(); // Clear cached user from guard

        $headers = ['Authorization' => 'Bearer ' . $token];
        fwrite(STDERR, "\nRequest headers after logout: " . json_encode($headers) . "\n");
        $profileResponse = $this->withHeaders($headers)
            ->getJson('/api/v1/user');
        fwrite(STDERR, "\nResponse headers after logout: " . json_encode($profileResponse->headers->all()) . "\n");
        fwrite(STDERR, "\nProfile response after logout: " . $profileResponse->getContent() . "\n");
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
        $profileResponse->assertStatus(401);
    }
} 