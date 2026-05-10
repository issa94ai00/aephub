<?php

namespace App\Domain\LiveSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UploadAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $session = $this->route('live_session');
        return $this->user()->id === $session->teacher_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,jpeg,png,jpg,webp,gif'],
            'type' => ['sometimes', 'in:pdf,image'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => __('validation.live_session.file_required'),
            'file.max' => __('validation.live_session.file_max'),
            'file.mimes' => __('validation.live_session.file_mimes'),
            'type.in' => __('validation.live_session.type_invalid'),
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
