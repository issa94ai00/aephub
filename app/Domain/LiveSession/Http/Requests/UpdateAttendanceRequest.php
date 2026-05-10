<?php

namespace App\Domain\LiveSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('attendance'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'last_position_ms' => ['sometimes', 'integer', 'min:0'],
            'duration_ms' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'last_position_ms.min' => __('validation.live_session.position_min'),
            'duration_ms.min' => __('validation.live_session.duration_min'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => __('validation.failed'),
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
