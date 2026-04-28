<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Support\ApiPagination;
use App\Support\EnrollmentPaymentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'study_term_id' => ['nullable', 'integer', 'exists:study_terms,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = Course::query()
            ->where('status', 'published')
            ->with(['teacher:id,name'])
            ->latest('id');

        if (! empty($data['study_term_id'])) {
            $termId = (int) $data['study_term_id'];
            $q->whereHas('studyTerms', function ($w) use ($termId): void {
                $w->where('study_terms.id', $termId);
            });
        }

        if (! empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $like = '%'.$needle.'%';
            $q->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('description_en', 'like', $like);
            });
        }

        return response()->json($q->paginate($perPage));
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        if ($course->status !== 'published') {
            if (! $user) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $role = strtolower((string) ($user->role ?? ''));
            $allowed = $role === 'admin'
                || ($role === 'teacher' && (int) $course->teacher_id === (int) $user->id);
            if (! $allowed) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $course->load([
            'teacher:id,name',
            'videos:id,course_id,title,title_en,description,description_en,storage_path,size_bytes,encrypted_sha256,status',
            'files:id,course_id,name,name_en,storage_disk,storage_path',
        ]);

        $enrollment = null;
        $enrollmentStatus = null;
        $isEnrolled = null;

        if ($user) {
            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            $enrollmentStatus = $enrollment?->status;
            $isEnrolled = $enrollmentStatus === 'approved';
        }

        $courseAccessActive = $enrollment !== null
            && $enrollment->status === 'approved'
            && ! $enrollment->access_locked;

        // Progressive unlock for students: limit returned videos to cumulative approved payments.
        $paymentProgress = null;
        if ($user && strtolower((string) ($user->role ?? '')) === 'student') {
            $paymentProgress = EnrollmentPaymentProgress::progressPayloadForStudent($course, $enrollment);
        }

        if ($user && strtolower((string) ($user->role ?? '')) === 'student' && $courseAccessActive) {
            $coursePrice = (int) ($course->price_cents ?? 0);
            if ($coursePrice === 0) {
                $course->setRelation('videos', $course->videos->sortBy('id')->values());
            } else {
                $allowed = EnrollmentPaymentProgress::unlockedVideoIdsForStudent((int) $user->id, $course);
                $allowedSet = array_fill_keys($allowed, true);
                $course->setRelation(
                    'videos',
                    $course->videos->filter(fn ($v) => isset($allowedSet[(int) $v->id]))->sortBy('id')->values()
                );
            }
        }

        $enrollmentPayload = null;
        if ($enrollment) {
            $enrollmentPayload = [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'access_locked' => (bool) $enrollment->access_locked,
                'paid_amount_cents' => (int) ($enrollment->paid_amount_cents ?? 0),
                'unlocked_videos_count' => (int) ($enrollment->unlocked_videos_count ?? 0),
                'unlocked_sessions_count' => (int) ($enrollment->unlocked_sessions_count ?? 0),
            ];
            if ($paymentProgress !== null) {
                $enrollmentPayload['paid_amount_cents'] = (int) $paymentProgress['approved_paid_total_cents'];
                $enrollmentPayload['unlocked_videos_count'] = (int) $paymentProgress['unlocked_videos_count'];
                $enrollmentPayload['unlocked_sessions_count'] = (int) $paymentProgress['unlocked_sessions_count'];
            }
        }

        return response()->json([
            'course' => $course,
            'enrollment_status' => $enrollmentStatus,
            'is_enrolled' => $isEnrolled,
            /** True when student may use course materials (approved and not access_locked). */
            'course_access_active' => $courseAccessActive ?? false,
            'enrollment' => $enrollmentPayload,
            'payment_progress' => $paymentProgress,
        ]);
    }

    /**
     * Course cover image: published courses are readable without JWT (for Image.network).
     * Draft/archived: requires valid Bearer JWT and admin or owning teacher.
     */
    public function cover(Request $request, Course $course): Response|JsonResponse
    {
        if (! $course->cover_image_path) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($course->status !== 'published') {
            $jwtUser = $this->optionalJwtUser($request);
            if (! $jwtUser) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $role = strtolower((string) ($jwtUser->role ?? ''));
            $allowed = $role === 'admin'
                || ($role === 'teacher' && (int) $course->teacher_id === (int) $jwtUser->id);
            if (! $allowed) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        return $this->coverFileResponse($course);
    }

    public function uploadCover(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseCoverUpload($request, $course);

        $request->validate([
            'image' => ['required_without:cover_image', 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'cover_image' => ['required_without:image', 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        if (! $this->persistCoverFromUploadedFiles($request, $course)) {
            return response()->json(['message' => 'image or cover_image file is required'], 422);
        }

        $course->load(['teacher:id,name']);

        return response()->json([
            'course' => $course,
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = Course::query()
            ->with(['teacher:id,name'])
            ->latest('id');

        if (! empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $q->where(function ($w) use ($needle): void {
                $like = '%'.$needle.'%';
                $w->where('title', 'like', $like)
                    ->orWhere('title_en', 'like', $like);
            });
        }

        $p = $q->paginate($perPage);

        return response()->json(ApiPagination::format($p));
    }

    /**
     * Courses assigned to the authenticated teacher (course.teacher_id = auth id).
     * Same payload shape as admin courses list for mobile clients.
     */
    public function teacherIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (! $user->canTeachCourses()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = Course::query()
            ->where('teacher_id', $user->id)
            ->with(['teacher:id,name'])
            ->latest('id');

        if (! empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $like = '%'.$needle.'%';
            $q->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('description_en', 'like', $like);
            });
        }

        $p = $q->paginate($perPage);

        return response()->json(ApiPagination::format($p));
    }

    public function adminStore(Request $request): JsonResponse
    {
        $course = Course::create($this->validatedAdminCourseForCreate($request));
        $this->persistCoverFromUploadedFiles($request, $course);
        $course->refresh()->load(['teacher:id,name']);

        return response()->json([
            'course' => $course,
        ], 201);
    }

    public function adminUpdate(Request $request, Course $course): JsonResponse
    {
        $data = $this->validatedAdminCourseForUpdate($request);
        if ($data !== []) {
            $course->update($data);
        }
        $this->persistCoverFromUploadedFiles($request, $course);
        $course->refresh()->load(['teacher:id,name']);

        return response()->json([
            'course' => $course,
        ]);
    }

    public function adminDestroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function adminAssignTeacher(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $teacher = User::findOrFail($data['teacher_id']);
        if (strtolower((string) ($teacher->role ?? '')) !== 'teacher') {
            return response()->json(['message' => 'teacher_id must belong to a teacher'], 422);
        }
        if (($teacher->teacher_approval_status ?? null) !== User::TEACHER_APPROVAL_APPROVED) {
            return response()->json(['message' => 'Teacher is not approved'], 422);
        }

        $course->forceFill(['teacher_id' => $teacher->id])->save();
        $course->load(['teacher:id,name']);

        return response()->json([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'teacher' => $course->teacher ? [
                    'id' => $course->teacher->id,
                    'name' => $course->teacher->name,
                ] : null,
            ],
        ]);
    }

    public function teacherStore(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $user->canTeachCourses()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->validatedTeacherCourseForCreate($request);
        $data['teacher_id'] = $user->id;

        $course = Course::create($data);
        $this->persistCoverFromUploadedFiles($request, $course);
        $course->refresh()->load(['teacher:id,name']);

        return response()->json([
            'course' => $course,
        ], 201);
    }

    public function teacherUpdate(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((int) $course->teacher_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->validatedTeacherCourseForUpdate($request);
        if ($data !== []) {
            $course->update($data);
        }
        $this->persistCoverFromUploadedFiles($request, $course);
        $course->refresh()->load(['teacher:id,name']);

        return response()->json([
            'course' => $course,
        ]);
    }

    public function students(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admin can view any course; teacher can view their own course only
        $role = strtolower((string) ($user->role ?? 'student'));
        if ($role === 'teacher' && (int) $course->teacher_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $status = (string) ($data['status'] ?? 'approved');
        $perPage = (int) ($data['per_page'] ?? 50);

        $q = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->with(['user:id,name,email'])
            ->latest('id');

        if ($status !== '') {
            $q->where('status', $status);
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
                'student' => $e->user ? [
                    'id' => $e->user->id,
                    'name' => $e->user->name,
                    'email' => $e->user->email,
                ] : null,
                'status' => $e->status,
                'access_locked' => (bool) $e->access_locked,
                'requested_at' => optional($e->requested_at)->toISOString(),
                'approved_at' => optional($e->approved_at)->toISOString(),
                'created_at' => optional($e->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAdminCourseForCreate(Request $request): array
    {
        $data = $request->validate(array_merge($this->adminCourseFieldRules(true), $this->courseCoverFieldRules()));

        return $this->stripCoverKeys($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAdminCourseForUpdate(Request $request): array
    {
        $data = $request->validate(array_merge($this->adminCourseFieldRules(false), $this->courseCoverFieldRules()));

        return $this->stripCoverKeys($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTeacherCourseForCreate(Request $request): array
    {
        $data = $request->validate(array_merge($this->teacherCourseFieldRules(true), $this->courseCoverFieldRules()));

        return $this->stripCoverKeys($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTeacherCourseForUpdate(Request $request): array
    {
        $data = $request->validate(array_merge($this->teacherCourseFieldRules(false), $this->courseCoverFieldRules()));

        return $this->stripCoverKeys($data);
    }

    /**
     * @return array<string, array<int, mixed|In|Exists>>
     */
    private function adminCourseFieldRules(bool $create): array
    {
        if ($create) {
            return [
                'teacher_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(function ($q) {
                        $q->where('role', 'admin')
                            ->orWhere(function ($teacherQ) {
                                $teacherQ->where('role', 'teacher')
                                    ->where('teacher_approval_status', User::TEACHER_APPROVAL_APPROVED);
                            });
                    }),
                ],
                'title' => ['required', 'string', 'max:255'],
                'title_en' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'description_en' => ['nullable', 'string'],
                'price_cents' => ['required', 'integer', 'min:0'],
                'currency' => ['required', 'string', 'max:16'],
                'sham_cash_code' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            ];
        }

        return [
            'teacher_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'admin')
                        ->orWhere(function ($teacherQ) {
                            $teacherQ->where('role', 'teacher')
                                ->where('teacher_approval_status', User::TEACHER_APPROVAL_APPROVED);
                        });
                }),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'description_en' => ['sometimes', 'nullable', 'string'],
            'price_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'max:16'],
            'sham_cash_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    /**
     * @return array<string, array<int, mixed|In>>
     */
    private function teacherCourseFieldRules(bool $create): array
    {
        if ($create) {
            return [
                'title' => ['required', 'string', 'max:255'],
                'title_en' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'description_en' => ['nullable', 'string'],
                'price_cents' => ['required', 'integer', 'min:0'],
                'currency' => ['required', 'string', 'max:16'],
                'sham_cash_code' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            ];
        }

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'description_en' => ['sometimes', 'nullable', 'string'],
            'price_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'max:16'],
            'sham_cash_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function courseCoverFieldRules(): array
    {
        return [
            'image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'cover_image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripCoverKeys(array $data): array
    {
        unset($data['image'], $data['cover_image']);

        return $data;
    }

    /**
     * Save cover from multipart `image` or `cover_image` (same as dedicated upload endpoint).
     */
    private function persistCoverFromUploadedFiles(Request $request, Course $course): bool
    {
        $file = $request->file('image') ?? $request->file('cover_image');
        if (! $file || ! $file->isValid()) {
            return false;
        }

        Validator::make(
            ['_cover' => $file],
            ['_cover' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp']]
        )->validate();

        $disk = 'local';
        $path = $file->store('course-covers/'.$course->id, $disk);

        if ($course->cover_image_path && Storage::disk($course->cover_image_disk ?: 'local')->exists($course->cover_image_path)) {
            Storage::disk($course->cover_image_disk ?: 'local')->delete($course->cover_image_path);
        }

        $course->forceFill([
            'cover_image_disk' => $disk,
            'cover_image_path' => $path,
        ])->save();

        return true;
    }

    private function authorizeCourseCoverUpload(Request $request, Course $course): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ($user->role === 'admin') {
            return;
        }
        abort_unless(
            $user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id,
            403
        );
    }

    private function optionalJwtUser(Request $request): ?User
    {
        if (! $request->bearerToken()) {
            return null;
        }
        try {
            JWTAuth::setRequest($request);
            $user = JWTAuth::parseToken()->authenticate();

            return $user instanceof User ? $user : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function coverFileResponse(Course $course): Response|JsonResponse
    {
        $disk = $course->cover_image_disk ?: 'local';
        if (! Storage::disk($disk)->exists($course->cover_image_path)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return Storage::disk($disk)->response($course->cover_image_path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
