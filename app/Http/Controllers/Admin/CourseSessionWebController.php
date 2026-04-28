<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseVideo;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CourseSessionWebController extends Controller
{
    public function index(Course $course): Response
    {
        $sessions = $course->sessions()
            ->withCount('videos')
            ->get();

        return AdminInertia::frame('admin.sessions.index', compact('course', 'sessions'));
    }

    public function create(Course $course): Response
    {
        return AdminInertia::frame('admin.sessions.create', compact('course'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $course->sessions()->create([
            'title' => $data['title'],
            'title_en' => $data['title_en'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.courses.sessions.index', $course)
            ->with('status', 'تم إنشاء الجلسة.');
    }

    public function edit(Course $course, CourseSession $session): Response
    {
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        return AdminInertia::frame('admin.sessions.edit', compact('course', 'session'));
    }

    public function update(Request $request, Course $course, CourseSession $session): RedirectResponse
    {
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $session->update([
            'title' => $data['title'],
            'title_en' => $data['title_en'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.courses.sessions.index', $course)
            ->with('status', 'تم تحديث الجلسة.');
    }

    public function destroy(Course $course, CourseSession $session): RedirectResponse
    {
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $session->delete();

        return redirect()
            ->route('admin.courses.sessions.index', $course)
            ->with('status', 'تم حذف الجلسة.');
    }

    public function videos(Course $course, CourseSession $session): Response
    {
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $videos = CourseVideo::query()
            ->where('course_id', $course->id)
            ->orderBy('id')
            ->get();

        $current = $session->videos()->get()->keyBy('id');

        return AdminInertia::frame('admin.sessions.videos', compact('course', 'session', 'videos', 'current'));
    }

    public function syncVideos(Request $request, Course $course, CourseSession $session): RedirectResponse
    {
        abort_unless((int) $session->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'video_ids' => ['nullable', 'array'],
            'video_ids.*' => ['integer'],
            'sort_order' => ['nullable', 'array'],
        ]);

        $selected = array_map('intval', $data['video_ids'] ?? []);
        $sortOrders = $data['sort_order'] ?? [];

        $sync = [];
        foreach ($selected as $videoId) {
            $sort = $sortOrders[$videoId] ?? 0;
            $sync[$videoId] = ['sort_order' => (int) $sort];
        }

        // Only allow videos that belong to this course
        $allowedIds = CourseVideo::query()
            ->where('course_id', $course->id)
            ->whereIn('id', array_keys($sync))
            ->pluck('id')
            ->all();
        $allowedMap = array_fill_keys(array_map('intval', $allowedIds), true);

        $filtered = [];
        foreach ($sync as $videoId => $pivot) {
            if (isset($allowedMap[(int) $videoId])) {
                $filtered[(int) $videoId] = $pivot;
            }
        }

        $session->videos()->sync($filtered);

        return redirect()
            ->route('admin.courses.sessions.videos', [$course, $session])
            ->with('status', 'تم حفظ فيديوهات الجلسة.');
    }
}

