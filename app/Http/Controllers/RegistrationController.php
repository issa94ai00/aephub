<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\User;
use App\Support\StudentAcademicProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function show(): Response
    {
        $universities = Schema::hasTable('universities')
            ? University::query()->orderBy('name')->get()
            : collect();

        $pickerConfig = [
            'apiBase' => url('/api/v1'),
            'initialStudyTermId' => old('account_type') === 'student' ? old('study_term_id') : null,
            'hasUniversities' => $universities->isNotEmpty(),
            'fetchErrorLabel' => __('site.form.academic_fetch_error'),
            'labels' => [
                'select_faculty' => __('site.form.select_faculty'),
                'select_year' => __('site.form.select_year'),
                'select_term' => __('site.form.select_term'),
            ],
        ];

        return Inertia::render('Site/Registration/Index', [
            'universities' => $universities
                ->map(fn (University $u) => [
                    'id' => $u->id,
                    'localized_name' => $u->localized_name,
                ])
                ->values()
                ->all(),
            'pickerConfig' => $pickerConfig,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isStudent = $request->input('account_type') === 'student';

        $data = $request->validate([
            'account_type' => ['required', 'in:student,teacher'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\s().-]+$/u'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['accepted'],
            'study_term_id' => [Rule::requiredIf($isStudent), 'nullable', 'integer', 'exists:study_terms,id'],
        ], [
            'terms_accepted.accepted' => __('registration.terms_validation'),
        ]);

        $isTeacher = $data['account_type'] === 'teacher';

        $academic = $isTeacher ? [] : StudentAcademicProfile::attributesFromStudyTermId(
            (int) $data['study_term_id'],
        );

        User::create(array_merge([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'terms_accepted_at' => now(),
            'password' => Hash::make($data['password']),
            'role' => $isTeacher ? 'teacher' : 'student',
            'teacher_approval_status' => $isTeacher ? User::TEACHER_APPROVAL_PENDING : User::TEACHER_APPROVAL_APPROVED,
            'device_lock_enabled' => true,
        ], $academic));

        if ($isTeacher) {
            return redirect()
                ->route('subscription.register')
                ->with('status', __('registration.teacher_pending'));
        }

        return redirect()
            ->route('subscription.register')
            ->with('status', __('registration.student_success'));
    }
}
