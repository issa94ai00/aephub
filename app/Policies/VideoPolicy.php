<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\User;
use App\Support\EnrollmentPaymentProgress;

class VideoPolicy
{
    public function view(User $user, CourseVideo $video): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher' && (int) $video->course->teacher_id === (int) $user->id) {
            return true;
        }

        if ($user->role !== 'student') {
            return false;
        }

        $hasEnrollment = CourseEnrollment::query()
            ->where('course_id', $video->course_id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('access_locked', false)
            ->exists();

        if (! $hasEnrollment) {
            return false;
        }

        $video->loadMissing('course');
        $allowed = EnrollmentPaymentProgress::unlockedVideoIdsForStudent((int) $user->id, $video->course);

        return in_array((int) $video->id, $allowed, true);
    }

    public function create(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'teacher'
            && (int) $course->teacher_id === (int) $user->id
            && ($user->teacher_approval_status ?? null) === User::TEACHER_APPROVAL_APPROVED;
    }

    public function delete(User $user, CourseVideo $video): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $video->loadMissing('course');

        return $user->role === 'teacher'
            && (int) $video->course->teacher_id === (int) $user->id
            && ($user->teacher_approval_status ?? null) === User::TEACHER_APPROVAL_APPROVED;
    }
}
