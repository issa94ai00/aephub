<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSessionAdminController extends Controller
{
    private function authorizeCourseManagement(Request $request, Course $course): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if (strtolower((string) ($user->role ?? '')) === 'admin') {
            return;
        }
        abort_unless(
            strtolower((string) ($user->role ?? '')) === 'teacher'
                && (int) $course->teacher_id === (int) $user->id,
            403
        );
    }

    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseManagement($request, $course);
        $sessions = $course->sessions()
            ->with(['videos:id,course_id,title,title_en,description,description_en,storage_path,size_bytes,encrypted_sha256,status'])
            ->withCount('videos')
            ->get(['id', 'course_id', 'title', 'title_en', 'sort_order', 'created_at', 'updated_at']);

        return response()->json(['sessions' => $sessions]);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseManagement($request, $course);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $session = $course->sessions()->create([
            'title' => $data['title'],
            'title_en' => $data['title_en'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return response()->json(['session' => $session], 201);
    }

    public function update(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        $this->authorizeCourseManagement($request, $course);
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $session->update($data);

        return response()->json(['session' => $session->fresh()]);
    }

    public function destroy(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        $this->authorizeCourseManagement($request, $course);
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $session->delete();

        return response()->json(['deleted' => true]);
    }

    public function syncVideos(Request $request, Course $course, CourseSession $session): JsonResponse
    {
        $this->authorizeCourseManagement($request, $course);
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:0'],
            'items.*.course_video_id' => ['required', 'integer', 'exists:course_videos,id'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $items = $data['items'];
        $videoIds = array_map(fn ($i) => (int) $i['course_video_id'], $items);
        $allowed = CourseVideo::query()
            ->where('course_id', $course->id)
            ->whereIn('id', $videoIds)
            ->pluck('id')
            ->all();
        $allowedMap = array_fill_keys(array_map('intval', $allowed), true);

        $sync = [];
        foreach ($items as $i) {
            $vid = (int) $i['course_video_id'];
            if (! isset($allowedMap[$vid])) {
                continue;
            }
            $sync[$vid] = ['sort_order' => (int) ($i['sort_order'] ?? 0)];
        }

        $session->videos()->sync($sync);

        $session->load(['videos:id,course_id,title,title_en,description,description_en,storage_path,size_bytes,encrypted_sha256,status']);

        return response()->json([
            'session' => $session,
        ]);
    }
}

