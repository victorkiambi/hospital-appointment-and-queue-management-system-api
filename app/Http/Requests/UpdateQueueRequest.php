<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only doctors or admins can update queue entries
        return auth()->check() && (auth()->user()->role === 'doctor' || auth()->user()->role === 'admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|in:waiting,called,completed,cancelled',
            'position' => 'sometimes|required|integer|min:1',
            'called_at' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
        ];
    }
}
