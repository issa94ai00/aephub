<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseFile;
use App\Models\CourseSession;
use App\Support\CourseVideoBlobUrls;
use App\Support\EnrollmentPaymentProgress;
use App\Support\MediaChunkingHints;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseSessionController extends Controller
{
    public function index(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $enrollment = $this->authorizeCourseAccess($user->id, $course->id);

        $course->load(['videos:id,course_id,title,title_en,description,description_en,storage_path,size_bytes,encrypted_sha256,status']);
        $totalVideos = $course->videos->count();
        $unlockedSessionIds = EnrollmentPaymentProgress::unlockedSessionIdsForStudent((int) $user->id, $course);
        $unlockedSessionSet = array_fill_keys($unlockedSessionIds, true);
        $allowedVideoIds = EnrollmentPaymentProgress::unlockedVideoIdsForStudent((int) $user->id, $course);

        $sessions = $course->sessions()
            ->withCount('videos')
            ->with(['videos:id,course_id,title,title_en,description,description_en,storage_path,size_bytes,encrypted_sha256,status'])
            ->get(['id', 'course_id', 'title', 'title_en', 'sort_order']);

        $attendedIds = \DB::table('course_session_attendances')
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->pluck('course_session_id')
            ->all();
        $attendedMap = array_fill_keys(array_map('intval', $attendedIds), true);

        $items = $sessions->map(function (CourseSession $s) use ($course, $attendedMap, $unlockedSessionSet) {
            $row = $s->toArray();
            $row['attended'] = isset($attendedMap[(int) $s->id]);
            $paymentUnlocked = isset($unlockedSessionSet[(int) $s->id]);
            $row['payment_unlocked'] = $paymentUnlocked;

            if (! $paymentUnlocked) {
                $row['videos'] = [];
                $row['videos_count'] = 0;
            } else {
                $row['videos'] = collect($row['videos'] ?? [])
                    ->map(function (array $video) use ($course): array {
                        $video['storage_disk'] = null;
                        $video['wasabi_object_key'] = null;
                        $video['wasabi_url'] = null;
                        $video['wasabi_temporary_url'] = null;

                        $backingFile = $this->resolveBackingCourseFilePath((int) $course->id, (string) ($video['storage_path'] ?? ''));
                        if (! $backingFile) {
                            return $video;
                        }

                        $video['storage_disk'] = $backingFile->storage_disk;
                        $video = array_merge($video, CourseVideoBlobUrls::wasabiStyleFields($course, $backingFile));

                        $video['playback'] = MediaChunkingHints::playbackHints(
                            isset($video['size_bytes']) ? (int) $video['size_bytes'] : null
                        );

                        return $video;
                    })
                    ->values()
                    ->all();
                $row['videos_count'] = count($row['videos']);
            }

            return $row;
        });

        $total = $sessions->count();
        $attended = count($attendedMap);
        $progressPercent = $total > 0 ? (int) round(($attended / $total) * 100) : 0;

        return response()->json([
            'sessions' => $items,
            'progress' => [
                'sessions_total' => $total,
                'sessions_attended' => $attended,
                'progress_percent' => $progressPercent,
            ],
            'unlock' => [
                'total_sessions' => $sessions->count(),
                'unlocked_sessions_count' => count($unlockedSessionIds),
                'total_videos' => $totalVideos,
                'unlocked_videos_count' => count($allowedVideoIds),
            ],
        ]);
    }

    private function resolveBackingCourseFilePath(int $expectedCourseId, string $videoStoragePath): ?CourseFile
    {
        $path = trim($videoStoragePath);
        if (! preg_match('#^/api/v1/courses/(\d+)/files/(\d+)/download$#', $path, $m)) {
            return null;
        }

        $courseId = (int) $m[1];
        $fileId = (int) $m[2];
        if ($courseId !== $expectedCourseId) {
            return null;
        }

        $file = CourseFile::query()->find($fileId);
        if (! $file || (int) $file->course_id !== $courseId) {
            return null;
        }

        return $file;
    }

    public function attend(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((int) $session->course_id !== (int) $course->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorizeCourseAccess($user->id, $course->id);

        \DB::table('course_session_attendances')->updateOrInsert(
            ['course_session_id' => $session->id, 'user_id' => $user->id],
            [
                'course_id' => $course->id,
                'attended_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
            ]
        );

        return response()->json(['attended' => true]);
    }

    private function authorizeCourseAccess(int $userId, int $courseId): ?CourseEnrollment
    {
        $enrollment = CourseEnrollment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();

        abort_unless($enrollment !== null, 423);

        if ($enrollment->access_locked) {
            throw new HttpException(423, 'Course access suspended');
        }

        return $enrollment;
    }
}
