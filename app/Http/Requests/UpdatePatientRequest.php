<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins or the patient themselves can update
        $user = auth()->user();
        $patientId = $this->route('patient');
        if (!$user) return false;
        if ($user->role === 'admin') return true;
        if ($user->role === 'patient' && $user->patient && $user->patient->id == $patientId) return true;
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medical_record_number' => 'sometimes|required|string|max:255|unique:patients,medical_record_number,' . $this->route('patient'),
        ];
    }
}
