<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\PaymentRequest;

/**
 * Cumulative approved installments → paid_amount_cents, unlocked_sessions_count, and unlocked_videos_count.
 *
 * Progressive unlock is based on **sessions** (ordered by sort_order, then id): the student receives the first
 * floor(ratio × total_sessions) sessions; every video attached to those sessions is playable.
 * If the course has no sessions, unlock falls back to the legacy rule (first floor(ratio × total_videos) videos by id).
 *
 * @see docs/session.md
 */
class EnrollmentPaymentProgress
{
    public static function approvedPaymentsTotalCents(int $userId, int $courseId): int
    {
        return (int) PaymentRequest::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'approved')
            ->sum('amount_paid_cents');
    }

    /**
     * Recompute paid total and unlock counts from all approved payment_requests for this enrollment.
     */
    public static function applyToEnrollment(CourseEnrollment $enrollment): void
    {
        $course = Course::query()->withCount(['videos', 'sessions'])->find($enrollment->course_id);
        if (! $course) {
            return;
        }

        $totalPaid = self::approvedPaymentsTotalCents((int) $enrollment->user_id, (int) $enrollment->course_id);
        $price = (int) ($course->price_cents ?? 0);

        if ($price <= 0) {
            $metrics = self::fullUnlockMetrics($course);
            $enrollment->paid_amount_cents = $totalPaid;
            $enrollment->unlocked_sessions_count = $metrics['unlocked_sessions'];
            $enrollment->unlocked_videos_count = $metrics['unlocked_videos'];
        } else {
            $ratio = min(1.0, $totalPaid / $price);
            $fullyPaid = $totalPaid >= $price;
            $metrics = self::unlockMetricsForRatio($course, $ratio, $fullyPaid);
            $enrollment->paid_amount_cents = $totalPaid;
            $enrollment->unlocked_sessions_count = $metrics['unlocked_sessions'];
            $enrollment->unlocked_videos_count = $metrics['unlocked_videos'];
        }

        $enrollment->save();
    }

    /**
     * Full course access after portal express enroll (symbolic payment); unlock mirrors a fully paid enrollment.
     */
    public static function applyPortalExpressEnrollment(CourseEnrollment $enrollment): void
    {
        $course = Course::query()->withCount(['videos', 'sessions'])->find($enrollment->course_id);
        if (! $course) {
            return;
        }

        $totalPaid = self::approvedPaymentsTotalCents((int) $enrollment->user_id, (int) $enrollment->course_id);
        $metrics = self::unlockMetricsForRatio($course, 1.0, true);

        $enrollment->forceFill([
            'paid_amount_cents' => $totalPaid,
            'unlocked_sessions_count' => $metrics['unlocked_sessions'],
            'unlocked_videos_count' => $metrics['unlocked_videos'],
        ])->save();
    }

    /**
     * @return array{unlocked_sessions: int, unlocked_videos: int}
     */
    private static function fullUnlockMetrics(Course $course): array
    {
        $totalSessions = (int) ($course->sessions_count ?? 0);
        $totalVideos = (int) ($course->videos_count ?? 0);

        return [
            'unlocked_sessions' => $totalSessions,
            'unlocked_videos' => $totalVideos,
        ];
    }

    /**
     * @return array{unlocked_sessions: int, unlocked_videos: int}
     */
    private static function unlockMetricsForRatio(Course $course, float $ratio, bool $fullyPaid = false): array
    {
        $ratio = max(0.0, min(1.0, $ratio));
        $totalSessions = (int) ($course->sessions_count ?? 0);
        $totalVideos = (int) ($course->videos_count ?? 0);

        if ($totalSessions > 0) {
            $unlockedSessions = $fullyPaid
                ? $totalSessions
                : (int) floor($totalSessions * $ratio);
            $videoIds = self::videoIdsForFirstNSessions($course, $unlockedSessions);
            if ($fullyPaid) {
                $videoIds = array_values(array_unique(array_merge($videoIds, self::orphanVideoIdsForCourse($course))));
            }

            return [
                'unlocked_sessions' => $unlockedSessions,
                'unlocked_videos' => count($videoIds),
            ];
        }

        $unlockedVideos = $totalVideos > 0 ? (int) floor($totalVideos * $ratio) : 0;

        return [
            'unlocked_sessions' => 0,
            'unlocked_videos' => $unlockedVideos,
        ];
    }

    /**
     * Video IDs attached to the first N sessions (course session order), unique.
     *
     * @return list<int>
     */
    private static function videoIdsForFirstNSessions(Course $course, int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        $sessions = $course->sessions()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take($n)
            ->with(['videos:id'])
            ->get();

        $ids = [];
        foreach ($sessions as $session) {
            foreach ($session->videos as $video) {
                $ids[] = (int) $video->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Course videos not linked to any session of this course.
     *
     * @return list<int>
     */
    private static function orphanVideoIdsForCourse(Course $course): array
    {
        return CourseVideo::query()
            ->where('course_id', $course->id)
            ->whereNotIn('id', function ($q) use ($course): void {
                $q->select('course_session_videos.course_video_id')
                    ->from('course_session_videos')
                    ->join('course_sessions', 'course_sessions.id', '=', 'course_session_videos.course_session_id')
                    ->where('course_sessions.course_id', $course->id);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Session IDs the student may access (ordered: sort_order, id); first N = floor(ratio × total_sessions).
     *
     * @return list<int>
     */
    public static function unlockedSessionIdsForStudent(int $userId, Course $course): array
    {
        $enrollment = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();

        if (! $enrollment) {
            return [];
        }

        $course->loadCount(['sessions', 'videos']);

        $totalSessions = (int) ($course->sessions_count ?? 0);
        $price = (int) ($course->price_cents ?? 0);

        if ($price <= 0) {
            return $course->sessions()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($totalSessions <= 0) {
            return [];
        }

        $approvedTotal = self::approvedPaymentsTotalCents($userId, $course->id);
        $ratio = min(1.0, $approvedTotal / $price);
        $n = $approvedTotal >= $price
            ? $totalSessions
            : (int) floor($totalSessions * $ratio);
        $n = max(0, min($totalSessions, $n));

        return $course->sessions()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take($n)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Video IDs the student may access (see class docblock).
     *
     * @return list<int>
     */
    public static function unlockedVideoIdsForStudent(int $userId, Course $course): array
    {
        $enrollment = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();

        if (! $enrollment) {
            return [];
        }

        $course->loadMissing([
            'videos' => fn ($q) => $q->orderBy('id')->select([
                'id', 'course_id', 'title', 'title_en', 'description', 'description_en',
                'storage_path', 'size_bytes', 'encrypted_sha256', 'status',
            ]),
        ]);
        $course->loadCount(['sessions', 'videos']);

        $price = (int) ($course->price_cents ?? 0);

        if ($price <= 0) {
            return $course->videos->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $totalSessions = (int) ($course->sessions_count ?? 0);
        $approvedTotal = self::approvedPaymentsTotalCents($userId, $course->id);
        $ratio = min(1.0, $approvedTotal / $price);

        if ($totalSessions > 0) {
            $n = $approvedTotal >= $price
                ? $totalSessions
                : (int) floor($totalSessions * $ratio);
            $n = max(0, min($totalSessions, $n));
            $ids = self::videoIdsForFirstNSessions($course, $n);
            if ($approvedTotal >= $price) {
                $ids = array_values(array_unique(array_merge($ids, self::orphanVideoIdsForCourse($course))));
            }

            return $ids;
        }

        $videos = $course->videos->sortBy('id')->values();
        $total = $videos->count();
        $nv = $total > 0 ? (int) floor($total * $ratio) : 0;
        $nv = max(0, min($total, $nv));

        return $videos->take($nv)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{
     *   course_price_cents: int,
     *   approved_paid_total_cents: int,
     *   percent_toward_course: int,
     *   remaining_cents: int,
     *   is_fully_paid: bool,
     *   can_submit_payment: bool,
     *   total_sessions: int,
     *   unlocked_sessions_count: int,
     *   total_videos: int,
     *   unlocked_videos_count: int
     * }
     */
    public static function progressPayloadForStudent(Course $course, ?CourseEnrollment $enrollment): array
    {
        $price = (int) ($course->price_cents ?? 0);
        $course->loadCount(['videos', 'sessions']);
        $totalVideos = (int) ($course->videos_count ?? 0);
        $totalSessions = (int) ($course->sessions_count ?? 0);

        if ($price <= 0) {
            return [
                'course_price_cents' => $price,
                'approved_paid_total_cents' => (int) ($enrollment?->paid_amount_cents ?? 0),
                'percent_toward_course' => 100,
                'remaining_cents' => 0,
                'is_fully_paid' => true,
                'can_submit_payment' => false,
                'total_sessions' => $totalSessions,
                'unlocked_sessions_count' => $totalSessions,
                'total_videos' => $totalVideos,
                'unlocked_videos_count' => $totalVideos,
            ];
        }

        $approvedTotal = $enrollment
            ? self::approvedPaymentsTotalCents((int) $enrollment->user_id, (int) $course->id)
            : 0;

        $percent = $price > 0
            ? (int) min(100, floor(($approvedTotal / $price) * 100))
            : 100;
        $remaining = max(0, $price - $approvedTotal);
        $isFullyPaid = $approvedTotal >= $price;

        $ratio = $price > 0 ? min(1.0, $approvedTotal / $price) : 1.0;
        $metrics = self::unlockMetricsForRatio($course, $ratio, $isFullyPaid);

        return [
            'course_price_cents' => $price,
            'approved_paid_total_cents' => $approvedTotal,
            'percent_toward_course' => $percent,
            'remaining_cents' => $remaining,
            'is_fully_paid' => $isFullyPaid,
            'can_submit_payment' => ! $isFullyPaid,
            'total_sessions' => $totalSessions,
            'unlocked_sessions_count' => $metrics['unlocked_sessions'],
            'total_videos' => $totalVideos,
            'unlocked_videos_count' => $metrics['unlocked_videos'],
        ];
    }
}
