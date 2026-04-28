<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseChatMessage;
use App\Models\CourseEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseChatController extends Controller
{
    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseAccess($request, $course);

        $messages = CourseChatMessage::query()
            ->where('course_id', $course->id)
            ->with(['user:id,name', 'file:id,name,storage_disk,storage_path'])
            ->latest('id')
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseAccess($request, $course);

        $data = $request->validate([
            'body' => ['nullable', 'string'],
            'type' => ['nullable', 'in:text,file'],
            'course_file_id' => ['nullable', 'integer', 'exists:course_files,id'],
        ]);

        $type = $data['type'] ?? ($data['course_file_id'] ? 'file' : 'text');

        $message = CourseChatMessage::create([
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'] ?? null,
            'type' => $type,
            'course_file_id' => $data['course_file_id'] ?? null,
        ]);

        return response()->json(['message' => $message->load('user:id,name')], 201);
    }

    private function authorizeCourseAccess(Request $request, Course $course): void
    {
        $user = $request->user();
        if (in_array($user->role, ['admin'], true)) {
            return;
        }
        if ($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id) {
            return;
        }
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();
        if (!$enrollment) {
            throw new HttpException(423, 'Enrollment not approved');
        }
        if ($enrollment->access_locked) {
            throw new HttpException(423, 'Course access suspended');
        }
    }
}
