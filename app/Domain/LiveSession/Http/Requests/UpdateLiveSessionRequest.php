<?php

namespace App\Domain\LiveSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateLiveSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('live_session'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'settings' => ['nullable', 'array'],
            'settings.recording_enabled' => ['boolean'],
            'settings.allow_chat' => ['boolean'],
            'settings.require_approval' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => __('validation.live_session.title_max'),
            'max_participants.max' => __('validation.live_session.max_participants_limit'),
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
