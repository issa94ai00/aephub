<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\UserNotification;
use App\Models\PaymentRequest;
use App\Models\StudyTerm;
use App\Support\AdminNotifier;
use App\Support\ApiPagination;
use App\Support\EnrollmentPaymentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'amount_paid_cents' => ['required', 'integer', 'min:0'],
            'university_id' => ['nullable', 'integer', 'exists:universities,id'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'study_year_id' => ['nullable', 'integer', 'exists:study_years,id'],
            'study_term_id' => ['nullable', 'integer', 'exists:study_terms,id'],
            // Backward-compat (will be replaced by IDs on Flutter)
            'university' => ['nullable', 'string', 'max:255'],
            'study_year' => ['nullable', 'string', 'max:255'],
            'study_term' => ['nullable', 'string', 'max:255'],
            'subject_name' => ['required', 'string', 'max:255'],
            'receipt' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp'], // 10MB
        ]);

        $course = Course::findOrFail($data['course_id']);

        $coursePrice = (int) ($course->price_cents ?? 0);
        $paid = (int) $data['amount_paid_cents'];

        if ($coursePrice > 0) {
            $alreadyApproved = EnrollmentPaymentProgress::approvedPaymentsTotalCents(
                (int) $request->user()->id,
                (int) $course->id
            );
            if ($alreadyApproved >= $coursePrice) {
                return response()->json([
                    'message' => 'Course is already fully paid. No further payment submissions are required.',
                ], 422);
            }
        }
        $percent = 0;
        if ($coursePrice > 0) {
            $percent = (int) floor(min(1, $paid / $coursePrice) * 100);
        } elseif ($coursePrice === 0 && $paid > 0) {
            $percent = 100;
        }

        $disk = 'local';
        $receiptPath = $request->file('receipt')->store('receipts', $disk);

        $termId = isset($data['study_term_id']) ? (int) $data['study_term_id'] : null;
        if ($termId !== null) {
            $term = StudyTerm::query()->with('studyYear.faculty')->find($termId);
            if (! $term) {
                return response()->json(['message' => 'Invalid study_term_id'], 422);
            }
            $faculty = $term->studyYear?->faculty;
            $university = $faculty?->university;

            $data['study_year_id'] = $term->study_year_id;
            $data['faculty_id'] = $faculty?->id;
            $data['university_id'] = $faculty?->university_id;

            // fallback strings for older admin views/reporting
            $data['university'] = $data['university'] ?? ($university?->localized_name ?? null);
            $data['study_year'] = $data['study_year'] ?? ((string) ($term->studyYear?->year_number ?? ''));
            $data['study_term'] = $data['study_term'] ?? ((string) $term->term_number);
        }

        if (empty($data['university']) || empty($data['study_year']) || empty($data['study_term'])) {
            return response()->json([
                'message' => 'Academic fields are required (either IDs or text fields).',
            ], 422);
        }

        $payment = PaymentRequest::create([
            'user_id' => $request->user()->id,
            'course_id' => $data['course_id'],
            'provider' => 'sham_cash',
            'status' => 'pending',
            'amount_paid_cents' => $paid,
            'progress_percent' => $percent,
            'university' => $data['university'],
            'university_id' => $data['university_id'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'study_year' => $data['study_year'],
            'study_year_id' => $data['study_year_id'] ?? null,
            'study_term' => $data['study_term'],
            'study_term_id' => $data['study_term_id'] ?? null,
            'subject_name' => $data['subject_name'],
            'receipt_storage_disk' => $disk,
            'receipt_path' => $receiptPath,
        ]);

        // optional: store profile fields for later
        $request->user()->forceFill([
            'university' => $data['university'],
            'university_id' => $data['university_id'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'study_year' => $data['study_year'],
            'study_year_id' => $data['study_year_id'] ?? null,
            'study_term' => $data['study_term'],
            'study_term_id' => $data['study_term_id'] ?? null,
        ])->save();

        AdminNotifier::notify(
            type: 'payment_request_created',
            title: 'إشعار دفع جديد',
            body: $request->user()->name.' ('.$request->user()->email.')'.($course ? ' • '.$course->title : ''),
            data: ['payment_request_id' => $payment->id, 'course_id' => $payment->course_id, 'user_id' => $payment->user_id]
        );

        return response()->json(['payment' => $payment], 201);
    }

    /**
     * الطالب: سجل طلبات الدفع الخاصة به (دفعات متعددة لكل دورة).
     */
    public function studentIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = PaymentRequest::query()
            ->where('user_id', $user->id)
            ->with(['course:id,title,title_en,price_cents'])
            ->latest('id');

        if (! empty($data['course_id'])) {
            $q->where('course_id', $data['course_id']);
        }
        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (PaymentRequest $pr) {
            return [
                'id' => $pr->id,
                'status' => $pr->status,
                'course_id' => $pr->course_id,
                'amount_paid_cents' => (int) ($pr->amount_paid_cents ?? 0),
                'installment_percent_of_course' => (int) ($pr->progress_percent ?? 0),
                'subject_name' => $pr->subject_name,
                'review_note' => $pr->review_note,
                'reviewed_at' => optional($pr->reviewed_at)->toIso8601String(),
                'course' => $pr->course ? [
                    'id' => $pr->course->id,
                    'title' => $pr->course->localized_title,
                    'price_cents' => (int) ($pr->course->price_cents ?? 0),
                ] : null,
                'created_at' => optional($pr->created_at)->toIso8601String(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function teacherIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = PaymentRequest::query()
            ->whereHas('course', function ($courseQ) use ($user) {
                $courseQ->where('teacher_id', $user->id);
            })
            ->with([
                'user:id,name,email',
                'course:id,title,title_en,teacher_id',
            ])
            ->latest('id');

        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (!empty($data['course_id'])) {
            $q->where('course_id', $data['course_id']);
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (PaymentRequest $pr) {
            return [
                'id' => $pr->id,
                'status' => $pr->status,
                'amount_paid_cents' => (int) ($pr->amount_paid_cents ?? 0),
                'installment_percent_of_course' => (int) ($pr->progress_percent ?? 0),
                'course' => $pr->course ? ['id' => $pr->course->id, 'title' => $pr->course->localized_title] : null,
                'student' => $pr->user ? ['id' => $pr->user->id, 'name' => $pr->user->name, 'email' => $pr->user->email] : null,
                'subject_name' => $pr->subject_name,
                'receipt' => [
                    'url' => url("/api/v1/teacher/payments/{$pr->id}/receipt"),
                ],
                'created_at' => optional($pr->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function teacherShow(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $paymentRequest->load([
            'user:id,name,email,university,study_year,study_term',
            'course:id,title,title_en,teacher_id',
        ]);

        if ((int) ($paymentRequest->course?->teacher_id ?? 0) !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $paymentRequest->toArray();
        $payload['receipt'] = [
            'url' => url("/api/v1/teacher/payments/{$paymentRequest->id}/receipt"),
        ];

        return response()->json(['payment' => $payload]);
    }

    public function teacherReceipt(Request $request, PaymentRequest $paymentRequest)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $paymentRequest->load(['course:id,teacher_id']);
        if ((int) ($paymentRequest->course?->teacher_id ?? 0) !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$paymentRequest->receipt_path) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        $disk = $paymentRequest->receipt_storage_disk ?: 'local';
        if (!Storage::disk($disk)->exists($paymentRequest->receipt_path)) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        return Storage::disk($disk)->download($paymentRequest->receipt_path);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = PaymentRequest::query()->with([
            'user:id,name,email',
            'course:id,title,title_en',
        ])->latest('id');

        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (!empty($data['course_id'])) {
            $q->where('course_id', $data['course_id']);
        }
        if (!empty($data['user_id'])) {
            $q->where('user_id', $data['user_id']);
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (PaymentRequest $pr) {
            return [
                'id' => $pr->id,
                'status' => $pr->status,
                'amount_paid_cents' => (int) ($pr->amount_paid_cents ?? 0),
                'installment_percent_of_course' => (int) ($pr->progress_percent ?? 0),
                'course' => $pr->course ? ['id' => $pr->course->id, 'title' => $pr->course->localized_title] : null,
                'student' => $pr->user ? ['id' => $pr->user->id, 'name' => $pr->user->name, 'email' => $pr->user->email] : null,
                'subject_name' => $pr->subject_name,
                'university' => $pr->university,
                'study_year' => $pr->study_year,
                'study_term' => $pr->study_term,
                'receipt' => [
                    'url' => url("/api/v1/admin/payments/{$pr->id}/receipt"),
                    'mime_type' => null,
                    'size_bytes' => null,
                ],
                'created_at' => optional($pr->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function adminShow(PaymentRequest $paymentRequest): JsonResponse
    {
        $paymentRequest->load([
            'user:id,name,email,university,study_year,study_term',
            'course:id,title,title_en,teacher_id',
        ]);

        $payload = $paymentRequest->toArray();
        $payload['receipt'] = [
            'url' => url("/api/v1/admin/payments/{$paymentRequest->id}/receipt"),
            'mime_type' => null,
            'size_bytes' => null,
        ];

        return response()->json(['payment' => $payload]);
    }

    public function adminReview(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($paymentRequest->status !== 'pending') {
            return response()->json(['message' => 'Payment already reviewed'], 409);
        }

        $paymentRequest->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
            'review_note' => $data['note'] ?? null,
        ])->save();

        if ($data['status'] === 'approved') {
            $enrollment = CourseEnrollment::firstOrNew([
                'course_id' => $paymentRequest->course_id,
                'user_id' => $paymentRequest->user_id,
            ]);

            if (! $enrollment->exists) {
                $enrollment->requested_at = Carbon::now();
            }

            $enrollment->status = 'approved';
            if (! $enrollment->approved_at) {
                $enrollment->approved_at = Carbon::now();
                $enrollment->approved_by = $request->user()->id;
            }
            $enrollment->save();

            EnrollmentPaymentProgress::applyToEnrollment($enrollment);

            UserNotification::create([
                'user_id' => $paymentRequest->user_id,
                'type' => 'payment_reviewed',
                'title' => 'تم قبول الدفع',
                'body' => 'تم قبول إشعار الدفع الخاص بك وتفعيل الدورة.',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'course_id' => $paymentRequest->course_id,
                    'status' => 'approved',
                ],
            ]);
        } else {
            UserNotification::create([
                'user_id' => $paymentRequest->user_id,
                'type' => 'payment_reviewed',
                'title' => 'تم رفض الدفع',
                'body' => ($data['note'] ?? null) ?: 'تم رفض إشعار الدفع. يرجى التواصل مع الإدارة.',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'course_id' => $paymentRequest->course_id,
                    'status' => 'rejected',
                ],
            ]);
        }

        return response()->json(['payment' => $paymentRequest]);
    }

    public function adminReceipt(PaymentRequest $paymentRequest)
    {
        if (!$paymentRequest->receipt_path) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        $disk = $paymentRequest->receipt_storage_disk ?: 'local';
        if (!Storage::disk($disk)->exists($paymentRequest->receipt_path)) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        return Storage::disk($disk)->download($paymentRequest->receipt_path);
    }
}
