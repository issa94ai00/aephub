<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\CourseVideoProgress;
use App\Support\ApiPagination;
use App\Support\EnrollmentPaymentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LearningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);
        $user = $request->user();

        $paginator = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->with(['course' => fn ($q) => $q->with(['teacher:id,name'])])
            ->latest('updated_at')
            ->paginate($perPage);

        $courseIds = $paginator->getCollection()->pluck('course_id')->unique()->values()->all();

        $videosByCourse = CourseVideo::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', 'active')
            ->get()
            ->groupBy('course_id');

        $allVideoIds = $videosByCourse->flatten()->pluck('id')->all();

        $progressByVideoId = CourseVideoProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('course_video_id', $allVideoIds)
            ->get()
            ->keyBy('course_video_id');

        $mapped = $paginator->getCollection()->map(function (CourseEnrollment $e) use ($videosByCourse, $progressByVideoId, $user) {
            $videos = $videosByCourse->get($e->course_id, collect());
            if (strtolower((string) ($user->role ?? '')) === 'student' && $e->course) {
                $allowed = EnrollmentPaymentProgress::unlockedVideoIdsForStudent((int) $user->id, $e->course);
                $allowedSet = array_fill_keys($allowed, true);
                $videos = $videos->filter(fn (CourseVideo $v) => isset($allowedSet[(int) $v->id]));
            }
            $stats = self::learningStatsForVideos($videos, $progressByVideoId);

            return [
                'enrollment' => [
                    'id' => $e->id,
                    'status' => $e->status,
                    'approved_at' => $e->approved_at?->toIso8601String(),
                ],
                'course' => $e->course,
                'learning' => $stats,
            ];
        });

        $paginator->setCollection($mapped->values());

        return response()->json(ApiPagination::format($paginator));
    }

    /**
     * @param  Collection<int, CourseVideo>  $videos
     * @param  Collection<int|string, CourseVideoProgress>  $progressByVideoId
     * @return array{progress_percent: int, videos_total: int, videos_with_progress: int, last_video: ?array<string, mixed>}
     */
    private static function learningStatsForVideos($videos, $progressByVideoId): array
    {
        if ($videos->isEmpty()) {
            return [
                'progress_percent' => 0,
                'videos_total' => 0,
                'videos_with_progress' => 0,
                'last_video' => null,
            ];
        }

        $sumPct = 0.0;
        $withProgress = 0;
        $lastProgress = null;
        $lastTs = null;

        foreach ($videos as $video) {
            $p = $progressByVideoId->get($video->id);
            $sumPct += self::videoPercent($video, $p);
            if ($p && ($p->position_ms > 0 || $p->completed)) {
                $withProgress++;
            }
            if ($p) {
                $ts = $p->updated_at?->getTimestamp() ?? 0;
                if ($lastTs === null || $ts > $lastTs) {
                    $lastTs = $ts;
                    $lastProgress = ['video' => $video, 'progress' => $p];
                }
            }
        }

        $progressPercent = (int) round($sumPct / $videos->count());
        $lastVideo = null;
        if ($lastProgress !== null) {
            $lastVideo = [
                'id' => $lastProgress['video']->id,
                'title' => $lastProgress['video']->localized_title,
                'position_ms' => $lastProgress['progress']->position_ms,
                'completed' => $lastProgress['progress']->completed,
                'updated_at' => $lastProgress['progress']->updated_at?->toIso8601String(),
            ];
        }

        return [
            'progress_percent' => $progressPercent,
            'videos_total' => $videos->count(),
            'videos_with_progress' => $withProgress,
            'last_video' => $lastVideo,
        ];
    }

    private static function videoPercent(CourseVideo $video, ?CourseVideoProgress $p): float
    {
        if (! $p) {
            return 0.0;
        }
        if ($p->completed) {
            return 100.0;
        }
        $durMs = (int) ($video->duration_seconds ?? 0) * 1000;
        if ($durMs <= 0) {
            return 0.0;
        }

        return min(100.0, ($p->position_ms / $durMs) * 100.0);
    }
}
