<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'patient']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth()->user();
        $rules = [
            'doctor_id' => 'required|exists:doctors,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled',
        ];
        if ($user && $user->role === 'admin') {
            $rules['patient_id'] = 'required|exists:patients,id';
        }
        return $rules;
    }
}
