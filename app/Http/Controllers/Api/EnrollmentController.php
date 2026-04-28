<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\PaymentRequest;
use App\Services\SiteSettingsService;
use App\Support\AdminNotifier;
use App\Support\ApiPagination;
use App\Support\EnrollmentPaymentProgress;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function __construct(
        private SiteSettingsService $siteSettings
    ) {}

    public function requestEnrollment(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $enrollment = CourseEnrollment::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'status' => 'pending',
                'requested_at' => Carbon::now(),
            ]
        );

        AdminNotifier::notify(
            type: 'enrollment_request_created',
            title: 'طلب تسجيل جديد',
            body: $user->name.' ('.$user->email.') • '.$course->title,
            data: ['enrollment_id' => $enrollment->id, 'course_id' => $course->id, 'user_id' => $user->id]
        );

        return response()->json(['enrollment' => $enrollment], 201);
    }

    /**
     * Portal mode (site score_degree = 0): approve enrollment, record a symbolic 1-cent payment without receipt, full unlock.
     */
    public function expressEnroll(Request $request, Course $course): JsonResponse
    {
        if ($this->siteSettings->scoreDegreeValue() !== '0') {
            return response()->json([
                'message' => 'Express enrollment is not enabled for this site configuration.',
            ], 403);
        }

        if ($course->status !== 'published') {
            return response()->json(['message' => 'Course is not available'], 403);
        }

        $user = $request->user();

        return DB::transaction(function () use ($user, $course): JsonResponse {
            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment && $enrollment->status === 'rejected') {
                return response()->json([
                    'message' => 'لا يمكن إعادة التسجيل في هذه الدورة بعد الرفض.',
                ], 422);
            }

            $hasExpress = PaymentRequest::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('provider', 'portal_express')
                ->where('status', 'approved')
                ->lockForUpdate()
                ->exists();

            if (! $hasExpress) {
                $uni = trim((string) ($user->university ?? ''));
                $year = trim((string) ($user->study_year ?? ''));
                $term = trim((string) ($user->study_term ?? ''));

                PaymentRequest::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'provider' => 'portal_express',
                    'status' => 'approved',
                    'amount_paid_cents' => 1,
                    'progress_percent' => 100,
                    'university' => $uni !== '' ? $uni : '—',
                    'university_id' => $user->university_id,
                    'faculty_id' => $user->faculty_id,
                    'study_year' => $year !== '' ? $year : '—',
                    'study_year_id' => $user->study_year_id,
                    'study_term' => $term !== '' ? $term : '—',
                    'study_term_id' => $user->study_term_id,
                    'subject_name' => $course->localized_title,
                    'receipt_storage_disk' => 'local',
                    'receipt_path' => null,
                    'reviewed_at' => Carbon::now(),
                    'reviewed_by' => null,
                ]);
            }

            $enrollment ??= new CourseEnrollment([
                'course_id' => $course->id,
                'user_id' => $user->id,
            ]);

            $enrollment->forceFill([
                'status' => 'approved',
                'requested_at' => $enrollment->requested_at ?? Carbon::now(),
                'approved_at' => Carbon::now(),
                'approved_by' => null,
            ])->save();

            EnrollmentPaymentProgress::applyPortalExpressEnrollment($enrollment->fresh());

            return response()->json([
                'enrollment' => $enrollment->fresh(),
                'already_enrolled' => $hasExpress,
            ], $hasExpress ? 200 : 201);
        });
    }

    public function approve(Request $request, Course $course): JsonResponse
    {
        $actor = $request->user();
        if (! $actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Teacher can only approve/reject enrollments for their own courses
        $actorRole = strtolower((string) ($actor->role ?? 'student'));
        if ($actorRole === 'teacher' && (int) $course->teacher_id !== (int) $actor->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:approved,rejected'],
        ]);

        $status = $data['status'] ?? 'approved';

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $enrollment->forceFill([
            'status' => $status,
            'approved_at' => $status === 'approved' ? Carbon::now() : null,
            'approved_by' => $actor->id,
        ])->save();

        if ($status === 'approved') {
            EnrollmentPaymentProgress::applyToEnrollment($enrollment);
            $enrollment->refresh();
        }

        UserNotification::create([
            'user_id' => $enrollment->user_id,
            'type' => 'enrollment_decision',
            'title' => $status === 'approved' ? 'تم قبول طلب التسجيل' : 'تم رفض طلب التسجيل',
            'body' => $status === 'approved'
                ? 'تم قبول طلب تسجيلك وتفعيل الدورة.'
                : 'تم رفض طلب تسجيلك. يمكنك التواصل مع المدرس/الإدارة للمزيد من التفاصيل.',
            'data' => [
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'status' => $status,
            ],
        ]);

        return response()->json(['enrollment' => $enrollment]);
    }

    /**
     * Suspend a student's access to course content (sessions, files, videos, chat).
     * Enrollment stays `approved`; use unlock to restore access.
     */
    public function lockAccess(Request $request, Course $course): JsonResponse
    {
        $actor = $this->authorizeTeacherOrAdminForCourse($request, $course);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        if ($enrollment->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved enrollments can be suspended',
            ], 422);
        }

        $enrollment->forceFill([
            'access_locked' => true,
            'access_locked_at' => Carbon::now(),
            'access_locked_by' => $actor->id,
        ])->save();

        UserNotification::create([
            'user_id' => $enrollment->user_id,
            'type' => 'enrollment_access_suspended',
            'title' => 'تم تعليق وصولك للمادة',
            'body' => 'تم تعليق وصولك إلى محتوى المقرر. تواصل مع الإدارة أو المدرس إذا كان ذلك خطأ.',
            'data' => [
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'access_locked' => true,
            ],
        ]);

        return response()->json([
            'enrollment' => $this->enrollmentAccessPayload($enrollment),
        ]);
    }

    /**
     * Restore course content access after lockAccess.
     */
    public function unlockAccess(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherOrAdminForCourse($request, $course);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $enrollment->forceFill([
            'access_locked' => false,
            'access_locked_at' => null,
            'access_locked_by' => null,
        ])->save();

        UserNotification::create([
            'user_id' => $enrollment->user_id,
            'type' => 'enrollment_access_resumed',
            'title' => 'تم تفعيل وصولك للمادة',
            'body' => 'تمت إعادة تفعيل وصولك إلى محتوى المقرر.',
            'data' => [
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'access_locked' => false,
            ],
        ]);

        return response()->json([
            'enrollment' => $this->enrollmentAccessPayload($enrollment),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = CourseEnrollment::query()
            ->with([
                'course:id,title,title_en',
                'user:id,name,email',
            ])
            ->latest('id');

        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (! empty($data['course_id'])) {
            $q->where('course_id', $data['course_id']);
        }
        if (! empty($data['user_id'])) {
            $q->where('user_id', $data['user_id']);
        }
        if (! empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $q->whereHas('user', function ($sub) use ($needle) {
                $sub->where('name', 'like', "%{$needle}%")
                    ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (CourseEnrollment $e) {
            return [
                'id' => $e->id,
                'course' => $e->course ? ['id' => $e->course->id, 'title' => $e->course->localized_title] : null,
                'student' => $e->user ? ['id' => $e->user->id, 'name' => $e->user->name, 'email' => $e->user->email] : null,
                'status' => $e->status,
                'access_locked' => (bool) $e->access_locked,
                'note' => null,
                'created_at' => optional($e->created_at)->toISOString(),
                'updated_at' => optional($e->updated_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function adminShow(CourseEnrollment $enrollment): JsonResponse
    {
        return response()->json([
            'enrollment' => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'user_id' => $enrollment->user_id,
                'status' => $enrollment->status,
                'access_locked' => (bool) $enrollment->access_locked,
                'note' => null,
                'created_at' => optional($enrollment->created_at)->toISOString(),
                'updated_at' => optional($enrollment->updated_at)->toISOString(),
            ],
        ]);
    }

    public function adminReview(Request $request, CourseEnrollment $enrollment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($enrollment->status !== 'pending') {
            return response()->json(['message' => 'Enrollment already reviewed'], 409);
        }

        $enrollment->forceFill([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'approved' ? Carbon::now() : null,
            'approved_by' => $request->user()->id,
        ])->save();

        if ($data['status'] === 'approved') {
            EnrollmentPaymentProgress::applyToEnrollment($enrollment);
        }

        $enrollment->refresh();

        return response()->json([
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'access_locked' => (bool) $enrollment->access_locked,
                'paid_amount_cents' => $enrollment->paid_amount_cents,
                'unlocked_videos_count' => $enrollment->unlocked_videos_count,
                'unlocked_sessions_count' => $enrollment->unlocked_sessions_count,
                'note' => $data['note'] ?? null,
            ],
        ]);
    }

    private function authorizeTeacherOrAdminForCourse(Request $request, Course $course): User
    {
        $actor = $request->user();
        if (! $actor) {
            throw new HttpResponseException(response()->json(['message' => 'Unauthorized'], 401));
        }

        $actorRole = strtolower((string) ($actor->role ?? 'student'));
        if ($actorRole === 'teacher' && (int) $course->teacher_id !== (int) $actor->id) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }
        if ($actorRole !== 'teacher' && $actorRole !== 'admin') {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }

        return $actor;
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentAccessPayload(CourseEnrollment $enrollment): array
    {
        $enrollment->refresh();

        return [
            'id' => $enrollment->id,
            'course_id' => $enrollment->course_id,
            'user_id' => $enrollment->user_id,
            'status' => $enrollment->status,
            'access_locked' => (bool) $enrollment->access_locked,
            'access_locked_at' => $enrollment->access_locked_at?->toIso8601String(),
            'access_locked_by' => $enrollment->access_locked_by,
        ];
    }
}
