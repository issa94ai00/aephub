<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class TeacherManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $pendingTeachers = User::query()
            ->where('role', 'teacher')
            ->where('teacher_approval_status', User::TEACHER_APPROVAL_PENDING)
            ->latest('id')
            ->get();

        $teacherOptions = User::query()
            ->assignableAsTeacher()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $selectedTeacherId = (int) $request->query('teacher_id', 0);

        $coursesQuery = Course::query()
            ->with('teacher:id,name,email,role,teacher_approval_status')
            ->latest('id');

        if ($selectedTeacherId > 0) {
            $coursesQuery->where('teacher_id', $selectedTeacherId);
        }

        $courses = $coursesQuery->paginate(20)->withQueryString();

        return AdminInertia::frame('admin.teachers.index', compact(
            'pendingTeachers',
            'teacherOptions',
            'courses',
            'selectedTeacherId'
        ));
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->role === 'teacher', 404);

        $user->forceFill([
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ])->save();

        return back()->with('status', __('admin.flash.teacher_approved'));
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->role === 'teacher', 404);

        $user->forceFill([
            'teacher_approval_status' => User::TEACHER_APPROVAL_REJECTED,
        ])->save();

        return back()->with('status', 'تم رفض طلب المدرس.');
    }

    public function reassignCourseTeacher(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
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
        ]);

        $course->update([
            'teacher_id' => (int) $data['teacher_id'],
        ]);

        return back()->with('status', __('admin.flash.course_teacher_changed'));
    }
}
