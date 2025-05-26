<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        $appointmentId = $this->route('appointment');
        if (!$user) return false;
        if ($user->role === 'admin') return true;
        $appointment = \App\Models\Appointment::find($appointmentId);
        if (!$appointment) return false;
        if ($user->role === 'doctor' && $appointment->doctor && $user->doctor && $appointment->doctor->id == $user->doctor->id) return true;
        if ($user->role === 'patient' && $appointment->patient && $user->patient && $appointment->patient->id == $user->patient->id) return true;
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
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'patient_id' => 'sometimes|required|exists:patients,id',
            'scheduled_at' => 'sometimes|required|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled',
        ];
    }
}
