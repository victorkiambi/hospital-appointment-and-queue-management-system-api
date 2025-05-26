<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins or the doctor themselves can update
        $user = auth()->user();
        $doctorId = $this->route('doctor');
        if (!$user) return false;
        if ($user->role === 'admin') return true;
        if ($user->role === 'doctor' && $user->doctor && $user->doctor->id == $doctorId) return true;
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
            'specialization' => 'sometimes|required|string|max:255',
            'availability' => 'nullable|array',
        ];
    }
}
