<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StudentAcademicProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'string', 'min:1', 'max:32', 'regex:/^[0-9+\s().-]+$/u'],
        ]);

        if ($data === []) {
            return response()->json([
                'message' => __('site.profile.no_fields_to_update'),
            ], 422);
        }

        $user->forceFill($data)->save();

        return response()->json(['user' => $user->fresh()]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return response()->json([
                'message' => __('site.profile.current_password_incorrect'),
            ], 422);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        return response()->json([
            'message' => __('site.profile.password_updated'),
            'user' => $user->fresh(),
        ]);
    }

    public function updateAcademic(Request $request): JsonResponse
    {
        $data = $request->validate([
            'study_term_id' => ['required', 'integer', 'exists:study_terms,id'],
        ]);

        $user = $request->user();
        $user->forceFill(StudentAcademicProfile::attributesFromStudyTermId(
            (int) $data['study_term_id'],
        ))->save();

        return response()->json([
            'message' => __('site.profile.academic_updated'),
            'user' => $user->fresh(),
        ]);
    }

    public function updateShamCashCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sham_cash_code' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (! array_key_exists('sham_cash_code', $data)) {
            return response()->json([
                'message' => __('site.profile.no_fields_to_update'),
            ], 422);
        }

        $raw = $data['sham_cash_code'];
        $code = null;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            $code = $trimmed !== '' ? $trimmed : null;
        }

        $user = $request->user();
        $user->forceFill(['sham_cash_code' => $code])->save();

        return response()->json([
            'message' => __('site.profile.sham_cash_updated'),
            'user' => $user->fresh(),
        ]);
    }
}

