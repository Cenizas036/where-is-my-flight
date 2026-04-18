<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GateUpdateRequest — Validates gate contribution submissions.
 */
class GateUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'flight_id'         => 'required|uuid|exists:flights,id',
            'gate_number'       => 'required|string|max:10|regex:/^[A-Za-z0-9]+$/',
            'terminal'          => 'nullable|string|max:10',
            'contribution_type' => 'nullable|in:gate_update,terminal_update,baggage_update,status_update',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'gate_number.regex'   => 'Gate must be alphanumeric (e.g., A12, B3, 42).',
            'gate_number.max'     => 'Gate number too long — max 10 characters.',
            'flight_id.exists'    => 'Flight not found.',
        ];
    }
}
