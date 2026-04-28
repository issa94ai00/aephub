<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Support\AdminNotifier;
use App\Support\StudentAcademicProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const PASSWORD_RESET_CODE_TTL_MINUTES = 15;

    private const PASSWORD_RESET_MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private SiteSettingsService $siteSettings
    ) {}

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'accept_terms' => $this->resolveAcceptTermsForValidation($request),
        ]);

        $role = $request->input('role') ?? 'student';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\s().-]+$/u'],
            'password' => ['required', 'string', 'min:8'],
            'accept_terms' => ['accepted'],
            'role' => ['nullable', Rule::in(['student', 'teacher'])],
        ];

        if ($role === 'student') {
            $rules['study_term_id'] = ['required', 'integer', 'exists:study_terms,id'];
        }

        $data = $request->validate($rules, [
            'accept_terms.accepted' => __('registration.terms_validation'),
        ]);

        $resolvedRole = $data['role'] ?? 'student';

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'terms_accepted_at' => now(),
            'password' => Hash::make($data['password']),
            'role' => $resolvedRole,
            'teacher_approval_status' => $resolvedRole === 'teacher'
                ? User::TEACHER_APPROVAL_PENDING
                : User::TEACHER_APPROVAL_APPROVED,
            'device_lock_enabled' => true,
        ];

        if ($resolvedRole === 'student') {
            $payload = array_merge($payload, StudentAcademicProfile::attributesFromStudyTermId(
                (int) $data['study_term_id'],
            ));
        }

        $user = User::create($payload);

        if ($user->role === 'teacher') {
            AdminNotifier::notify(
                type: 'teacher_registration_created',
                title: 'طلب تسجيل مدرس جديد',
                body: $user->name.' ('.$user->email.')',
                data: ['teacher_user_id' => $user->id, 'status' => $user->teacher_approval_status]
            );

            return response()->json([
                'message' => 'تم إنشاء حساب المدرس بنجاح وهو الآن بانتظار موافقة مدير النظام.',
                'approval_status' => $user->teacher_approval_status,
                'user' => $user,
            ], 201);
        }

        $token = auth('api')->login($user);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'score_degree' => $this->siteSettings->scoreDegreeValue(),
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            SecurityEvent::create([
                'user_id' => null,
                'device_id' => $request->header('X-Device-Id'),
                'type' => 'login_failed',
                'payload' => ['email' => $credentials['email']],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();

        if ($user && (($user->status ?? User::STATUS_ACTIVE) === User::STATUS_DELETED)) {
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'message' => 'تم حذف هذا الحساب ولا يمكن تسجيل الدخول.',
                'status' => User::STATUS_DELETED,
            ], 403);
        }

        if ($user && (($user->status ?? User::STATUS_ACTIVE) === User::STATUS_FROZEN)) {
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'message' => 'Account is frozen',
                'status' => User::STATUS_FROZEN,
            ], 423);
        }

        if (
            $user
            && strtolower((string) $user->role) === 'teacher'
            && $user->teacher_approval_status !== User::TEACHER_APPROVAL_APPROVED
        ) {
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'message' => 'حساب المدرس بانتظار موافقة مدير النظام.',
                'approval_status' => $user->teacher_approval_status,
            ], 403);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'score_degree' => $this->siteSettings->scoreDegreeValue(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Logged out']);
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'score_degree' => $this->siteSettings->scoreDegreeValue(),
        ]);
    }

    public function adminResetDevice(User $user): JsonResponse
    {
        $user->forceFill([
            'locked_device_id' => null,
            'locked_device_at' => null,
        ])->save();

        return response()->json(['message' => 'Device lock reset']);
    }

    /**
     * Send a numeric reset code to the user's email (same response if email is unknown).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])
            ->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

            PasswordResetCode::query()->updateOrCreate(
                ['email' => $user->email],
                [
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(self::PASSWORD_RESET_CODE_TTL_MINUTES),
                    'failed_attempts' => 0,
                ],
            );

            try {
                Mail::to($user->email)->send(
                    new PasswordResetCodeMail($code, self::PASSWORD_RESET_CODE_TTL_MINUTES),
                );
            } catch (\Throwable $e) {
                Log::error('password_reset_mail_failed', [
                    'email' => $user->email,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'تعذر إرسال البريد الإلكتروني. حاول مرة أخرى لاحقاً.',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'إذا كان البريد مسجّلاً لدينا، ستصلك رسالة تحتوي على رمز إعادة التعيين.',
        ]);
    }

    /**
     * Verify the emailed code and set a new password.
     */
    public function resetPasswordWithCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])
            ->first();

        if (! $user) {
            return response()->json(['message' => 'رمز غير صالح أو منتهي الصلاحية.'], 422);
        }

        $record = PasswordResetCode::query()->where('email', $user->email)->first();

        if (! $record || $record->expires_at->isPast()) {
            $record?->delete();

            return response()->json(['message' => 'رمز غير صالح أو منتهي الصلاحية.'], 422);
        }

        if (! Hash::check($data['code'], $record->code_hash)) {
            $record->increment('failed_attempts');
            $record->refresh();

            if ($record->failed_attempts >= self::PASSWORD_RESET_MAX_CODE_ATTEMPTS) {
                $record->delete();
            }

            return response()->json(['message' => 'رمز غير صالح أو منتهي الصلاحية.'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        $record->delete();

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح.']);
    }

    /**
     * Use the first present key (order matters). Avoid ?? so explicit `false` is not skipped.
     */
    private function resolveAcceptTermsForValidation(Request $request): mixed
    {
        foreach (['accept_terms', 'terms_accepted', 'acceptTerms'] as $key) {
            if ($request->exists($key)) {
                return $this->normalizeAcceptTermsValue($request->input($key));
            }
        }

        return null;
    }

    /**
     * Coerce common API / form encodings so Laravel's `accepted` rule passes.
     * Flutter & multipart often send strings; JSON may use bool or int.
     */
    private function normalizeAcceptTermsValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => $value,
            };
        }

        if (is_float($value)) {
            if ($value === 1.0) {
                return true;
            }
            if ($value === 0.0) {
                return false;
            }

            return $value;
        }

        if (is_string($value)) {
            $v = strtolower(trim($value, " \t\n\r\0\x0B\""));

            if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($v, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $value;
    }
}
