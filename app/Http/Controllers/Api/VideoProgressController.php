<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseVideo;
use App\Models\CourseVideoProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoProgressController extends Controller
{
    public function store(Request $request, CourseVideo $video): JsonResponse
    {
        $this->authorize('view', $video);

        if ($video->status !== 'active') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'position_ms' => ['required', 'integer', 'min:0', 'max:864000000'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $positionMs = (int) $data['position_ms'];
        $completed = (bool) ($data['completed'] ?? false);

        $durMs = (int) ($video->duration_seconds ?? 0) * 1000;
        if ($durMs > 0) {
            $positionMs = min($positionMs, $durMs);
            if ($positionMs >= (int) floor($durMs * 0.95)) {
                $completed = true;
            }
        }

        $row = CourseVideoProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'course_video_id' => $video->id,
            ],
            [
                'position_ms' => $positionMs,
                'completed' => $completed,
            ]
        );

        return response()->json([
            'progress' => [
                'course_video_id' => $row->course_video_id,
                'position_ms' => $row->position_ms,
                'completed' => $row->completed,
                'updated_at' => $row->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
